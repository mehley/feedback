<?php

    // Last Update: 10.02.2017

//increase a transfer timeout in seconds (default 60)
    ini_set("default_socket_timeout", 180);

    /**
     * Send a mail over service mailer
     * REQUIRED PARAMS:
     * @param $service_url              - full URL of the SOAP service
     * @param $app                      - name of the app as specified in the db
     * @param $password                 - app password as specified in the db
     * @param $to                       - TO email field. Multiple emails can be separated by comma
     * @param $from                     (mixed)             - FROM email field. (String or array) If an array is used,
     *                                  the second item is the name
     * @param $subject                  - subject
     * @param $message                  - message. Can be of type text or html
     *
     * OPTIONAL PARAMS:
     * @param array $attachments        - array with attachments (example array on the bottom)
     * @param string $cc                - CC email field. Multiple emails can be separated by comma
     * @param string $bcc               - BCC email field. Multiple emails can be separated by comma
     * @param string $reply_to          - reply to email field. Multiple emails can be separated by comma
     * @param string $read_receipt_to   - send confirmation to this email address
     * @param bool $ishtml              - is this html (true) or text email (false)?
     * @param bool $nolog               - should the email message be logged?
     * @param array $extra              - additinal params. eg. 'nosend' => bool, will not send the email but it will
     *                                  be logged, good for testing.
     * @return string                   - will return a "OK" if the email was sent or anything else of there were some
     *                                  errors
     *
     *
     * attachments examples:
     * array('type' => 'local', 'path' => '/path/to/file.txt')     //transfer a local file to the mail service
     * array('type' => 'absolute', 'path' => '/path/to/file.txt')  //do not transfer, use a file on the remote server
     * (absolute path)
     *
     * If you want to attach the file as an inline attachment. Use the two arrays above but add also a 'cid' key.
     * example: array('type' => 'local', 'path' => '/path/to/file.txt', 'cid' => 'myPic') in your message you can
     * include the attachments like this: <img src="cid:myPic">
     *
     * If you want to set a different filename you can use the 'filename' key:
     * array('type' => 'local', 'path' => '/tmp/i3d933d', 'filename' => 'attachment.pdf')
     *
     * to attach multiple attachments, create a multidimensional array like this:
     * $attachments[] = array('type' => ...);
     *
     */
    function sendMailOverService($service_url, $app, $password, $to, $from, $subject, $message, $attachments = array(), $cc = '', $bcc = '', $reply_to = '', $read_receipt_to = '', $ishtml = true, $nolog = false, $extra = array())
    {
        $errors = array();
        $service = new SoapClient(null, array('cache_wsdl' => WSDL_CACHE_NONE, 'location' => $service_url, 'uri' => $service_url, 'trace' => 1, 'cache_wsdl' => WSDL_CACHE_NONE));

        //add function arguments to array. These will be passed to the service
        $params = array(
            'app'             => $app,
            'password'        => $password,
            'to'              => $to,
            'from'            => $from,
            'subject'         => $subject,
            'message'         => $message,
            'attachments'     => $attachments,
            'cc'              => $cc,
            'bcc'             => $bcc,
            'reply_to'        => $reply_to,
            'read_receipt_to' => $read_receipt_to,
            'ishtml'          => $ishtml,
            'nolog'           => $nolog,
            'extra'           => $extra,
            //additional params
            'secure'          => $_SERVER["REQUEST_SCHEME"] == 'https' ? 1 : 0,
        );


        //check if all required fields are set:
        $required_params = array('to', 'from', 'subject', 'message');
        foreach ($required_params as $param) {
            if (!isset($params[$param]) || (isset($params[$param]) && empty($params[$param]))) {
                $errors[] = "Required Param '{$param}' is not set! ";
            }
        }

        //do we have attachments?
        if (isset($params['attachments'])) {

            //do we have a single array or multiple arrays?
            if (isset($params['attachments']['type'])) {
                $params['attachments'] = array($params['attachments']);
            }


            $attachments_on_server = array();

            if (isset($params['attachments']) && !empty($params['attachments'])) {
                foreach ($params['attachments'] as &$file) {
                    //add local file (will be transferred to the mail service)
                    if ($file['type'] == 'local') {
                        if (file_exists($file['path'])) {
                            $added = $service->addAttachment(
                                base64_encode(gzcompress(file_get_contents($file['path']))),
                                isset($file['filename']) ? $file['filename'] : basename($file['path']),
                                isset($file['cid']) ? $file['cid'] : ''
                            );

                            $attachments_on_server[] = $added;
                            $file['attachment_on_server'] = $added;

                            if (!$added) {
                                $errors[] = "Local file {$file['path']} could not be added on the remote server";
                            }
                        } else {
                            $errors[] = "Local Attachment file not found: " . $file['path'];
                        }
                    }

                    //add file with an absolute path (will not be transferred to the mail service)
                    if ($file['type'] == 'absolute') {
                        $added = $service->addAttachmentAbsolute(
                            $file['path'],
                            isset($file['cid']) ? $file['cid'] : ''
                        );
                        $attachments_on_server[] = $added;
                        $file['attachment_on_server'] = $added;


                        if (!$added) {
                            $errors[] = "Absolute file {$file['path']} could not be added on the remote server";
                        }

                    }

                }
            }
        }

        //----------------------------------------------------------
        if (!empty($errors)) {
            //do we have any errors?
            return implode("\n", $errors);

        } else {
            //no errors found, try to send the mail:
            try {
                return $service->send($params);
            } catch (SoapFault $e) {


                //return $service->__getLastRequest();

                return "SOAP ERROR : " . $e->faultcode . ' - ' . $e->faultstring;
            }

        }

    }