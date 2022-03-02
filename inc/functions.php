<?php

/**
 * @param $identifier
 * @param $lang
 * @return array|string|string[]|null
 */
function createInputText_multiple($number, $lang, $id_form_element)
{
     // Sachen aus der Datenbank holen
    $sql = 'SELECT * FROM translations WHERE id_form_elements=:id_form_elements';
    $params = [
        ':id_form_elements' => $id_form_element
    ];
    $data = DB::fromDatabase($sql,'@line', $params);

    if (!empty($data)) {
        $data_language = json_decode($data[$lang], true);

        $template = '
            <div class="container d-inline"><div class="row"><div class="col-xxl-12">
            <div class="mb-3"><label class="form-label" for="form_field_' . $data['id_form_elements'] . '">@label@</label>
            <textarea class="form-control" placeholder="@placeholder@" id="form_field_' . $data['id_form_elements'] . '" name="form_field_' . $data['id_form_elements'] . '">@value@</textarea></div>
            </div>
            </div></div><hr>
            ';

        $template = preg_replace('/@value@/', $data_language['value'], $template);
        $template = preg_replace('/@label@/', $data_language['label'], $template);
        $template = preg_replace('/@placeholder@/', $data_language['placeholder'], $template);

        if (preg_match('/@number@/', $template)){
            $template = preg_replace('/@number@/', $number.'.', $template);
            $number++;
        }

        echo($template);
    }

    return $number;
}

/**
 * @param $identifier
 * @param $lang
 * @return array|string|string[]|null
 */
function createRadios($number, $identifier, $lang, $radiocounter, $group)
{
    $showElement = false;
    $sql = 'SELECT id FROM form_elements WHERE identifier=:identifier';
    $params = [
        ':identifier' => $identifier
    ];
    $id_form_element = DB::fromDatabase($sql,'@simple', $params);

    if (!empty($id_form_element)) {
        //Check if element is shown for current group
        $sql = 'SELECT * FROM form_elements2groups WHERE id_form_elements=:id_form_elements';
        $params = [
            ':id_form_elements' => $id_form_element
        ];
        $element2group = fromDatabase($sql, $params, 1);

        if (empty($element2group)) {
            $showElement = true;
        } else {
            foreach ($element2group as $key => $value) {
                if ($value['id_groups'] == $group) {
                    $showElement = true;
                }
            }
        }

        if ($showElement) {
            // Sachen aus der Datenbank holen
            $sql = 'SELECT * FROM translations WHERE id_form_elements=:id_form_elements';
            $params = [
                ':id_form_elements' => $id_form_element
            ];
            $data = fromDatabase($sql, $params);

            if (!empty($data)) {
                $counter = 1;
                $data_language = json_decode($data[$lang], true);
                $template = '<div class="container d-inline"><div class="row"><div class="col-xxl-8">';
                $template .= '<fieldset class="custom_border"><label class="form-label">@label@</label>';

                $template .= '<input type="hidden" name="form_field_' . $data['id_form_elements'] . '">';
                foreach ($data_language as $key => $value) {
                    if (strpos($key, 'label-opt') === 0) {
                        $template .= '<div class="form-check">
                  
                    <input class="form-check-input required_radio" type="radio" id="radio_field_' . $radiocounter . '"
                     name="form_field_' . $data['id_form_elements'] . '" value="@value' . $counter . '@" required>
                    <label class="form-check-label" for="radio_field_' . $radiocounter . '">@label-opt' . $counter . '@</label></div>';
                        $template = preg_replace('/@label-opt' . $counter . '@/', $value, $template);
                        $template = preg_replace('/@value' . $counter . '@/', $data_language['value' . $counter], $template);

                        $counter++;
                        $radiocounter++;
                    }
                }

                $template = preg_replace('/@label@/', $data_language['label'], $template);

                $template .= '</fieldset><div class="col-xxl-4"></div></div></div>';

                if (preg_match('/@number@/', $template)){
                    $template = preg_replace('/@number@/', $number.'.', $template);
                    $number++;
                }

                echo($template);
            }
        }
    }
    return $number;
}

/**
 * @param $identifier
 * @param $lang
 * @return array|string|string[]|null
 */
function createRating($number, $lang, $id_form_element, $count)
{

    // Sachen aus der Datenbank holen
    $sql = 'SELECT * FROM translations WHERE id_form_elements=:id_form_elements';
    $params = [
        ':id_form_elements' => $id_form_element
    ];
    $data = DB::fromDatabase($sql,'@line', $params);


    $counter = $count * 10 + 1;
    $counter2 = $counter + 1;
    $counter3 = $counter2 + 1;
    $counter4 = $counter3 + 1;
    $counter5 = $counter4 + 1;

    if (!empty($data)) {
        $data_language = json_decode($data[$lang], true);

        if (preg_match('/@number@/', $data_language['label'])){
            $data_language['label'] = preg_replace('/@number@/', $number.'.', $data_language['label']);
            $number++;
        }

        echo '<div class="container d-inline">
      <div class="row">';
        echo '<div class="col-xxl-10"><p>' . $data_language['label'] . '</p></div>';
        echo '<div class="col-xxl-2 rate">
        <input class="required-rating" type="radio" id="star' . $counter . '" name="form_field_' . $data['id_form_elements'] . '" value="5" required/>
        <label for="star' . $counter . '">5 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter2 . '" name="form_field_' . $data['id_form_elements'] . '" value="4" required/>
        <label for="star' . $counter2 . '">4 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter3 . '" name="form_field_' . $data['id_form_elements'] . '" value="3" required/>
        <label for="star' . $counter3 . '">3 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter4 . '" name="form_field_' . $data['id_form_elements'] . '" value="2" required/>
        <label for="star' . $counter4 . '">2 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter5 . '" name="form_field_' . $data['id_form_elements'] . '" value="1" required/>
        <label for="star' . $counter5 . '">1 star</label>
    </div>
    </div>
    </div>
     <hr>';
    }

    return $number;
}

/**
 * @param $identifier
 * @param $lang
 * @return array|string|string[]|null
 */
function createRating10($number, $lang, $id_form_element, $count)
{

    // Sachen aus der Datenbank holen
    $sql = 'SELECT * FROM translations WHERE id_form_elements=:id_form_elements';
    $params = [
        ':id_form_elements' => $id_form_element
    ];
    $data = DB::fromDatabase($sql,'@line', $params);


    $counter = $count * 10 + 1;
    $counter2 = $counter + 1;
    $counter3 = $counter2 + 1;
    $counter4 = $counter3 + 1;
    $counter5 = $counter4 + 1;
    $counter6 = $counter5 + 1;
    $counter7 = $counter6 + 1;
    $counter8 = $counter7 + 1;
    $counter9 = $counter8 + 1;
    $counter10 = $counter9 + 1;

    if (!empty($data)) {
        $data_language = json_decode($data[$lang], true);

        if (preg_match('/@number@/', $data_language['label'])){
            $data_language['label'] = preg_replace('/@number@/', $number.'.', $data_language['label']);
            $number++;
        }

        echo '<div class="container d-inline">
      <div class="row">';
        echo '<div class="col-xxl-8"><p>' . $data_language['label'] . '</p></div>';
        echo '<div class="col-xxl-4 rate">
        <input class="required-rating" type="radio" id="star' . $counter10 . '" name="form_field_' . $data['id_form_elements'] . '" value="10" required/>
        <label for="star' . $counter10 . '">10 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter9 . '" name="form_field_' . $data['id_form_elements'] . '" value="9" required/>
        <label for="star' . $counter9 . '">9 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter8 . '" name="form_field_' . $data['id_form_elements'] . '" value="8" required/>
        <label for="star' . $counter8 . '">8 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter7 . '" name="form_field_' . $data['id_form_elements'] . '" value="7" required/>
        <label for="star' . $counter7 . '">7 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter6 . '" name="form_field_' . $data['id_form_elements'] . '" value="6" required/>
        <label for="star' . $counter6 . '">6 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter5 . '" name="form_field_' . $data['id_form_elements'] . '" value="5" required/>
        <label for="star' . $counter5 . '">5 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter4 . '" name="form_field_' . $data['id_form_elements'] . '" value="4" required/>
        <label for="star' . $counter4 . '">4 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter3 . '" name="form_field_' . $data['id_form_elements'] . '" value="3" required/>
        <label for="star' . $counter3 . '">3 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter2 . '" name="form_field_' . $data['id_form_elements'] . '" value="2" required/>
        <label for="star' . $counter2 . '">2 stars</label>
        <input class="required-rating" type="radio" id="star' . $counter . '" name="form_field_' . $data['id_form_elements'] . '" value="1" required/>
        <label for="star' . $counter . '">1 star</label>
    </div>
    </div>
    </div>
     <hr>';
    }

    return $number;
}

/**
 * @param $lang
 * @return array|string|string[]|null
 */
function createText($number, $lang, $id_form_element)
{
    $replaced_number = false;

    // Sachen aus der Datenbank holen
    $sql = 'SELECT * FROM translations WHERE id_form_elements=:id_form_elements';
    $params = [
        ':id_form_elements' => $id_form_element
    ];
    $data = DB::fromDatabase($sql,'@line', $params);

    if (!empty($data)){
        $data_language = json_decode($data[$lang], true);

        if (!empty($data_language['text'])){
            if (preg_match('/@number@/', $data_language['text'])){
                $data_language['text'] = preg_replace('/@number@/', $number.'.', $data_language['text']);
                $replaced_number = true;
            }
            echo '<p>'.$data_language['text'].'</p><hr>';
        }
    }
    if ($replaced_number){
        $number++;
    }

    return $number;
}

/**
 * @param $lang
 * @return array|string|string[]|null
 */
function createInputText($number, $lang, $id_form_element)
{
    $replaced_number = false;

    // Sachen aus der Datenbank holen
    $sql = 'SELECT * FROM translations WHERE id_form_elements=:id_form_elements';
    $params = [
        ':id_form_elements' => $id_form_element
    ];
    $data = DB::fromDatabase($sql,'@line', $params);

    if (!empty($data)){
        $data_language = json_decode($data[$lang], true);

        if (!empty($data_language['label'])){
            if (preg_match('/@number@/', $data_language['label'])){
                $data_language['label'] = preg_replace('/@number@/', $number.'.', $data_language['label']);
                $replaced_number = true;
            }
            echo '<div class="container d-inline"><div class="row"><div class="col-xxl-12">
            <div class="mb-3"><label class="form-label" for="form_field_' . $data['id_form_elements'] . '">'.$data_language['label'].'</label>
            <input class="form-control" type="text" placeholder="'.$data_language['label'].'" id="form_field_' . $data['id_form_elements'] . '" name="form_field_' . $data['id_form_elements'] . '"></div>
            </div>
            </div></div><hr>';
        }
    }
    if ($replaced_number){
        $number++;
    }

    return $number;
}

/**
 * @param $identifier
 * @param $lang
 * @return array|string|string[]|null
 */
function translate($identifier, $lang = '')
{
    if (empty($lang)){
        $lang = $_SESSION['language'];
    }
    $sql_translation = 'SELECT '.$lang.' FROM website_translations WHERE identifier=:identifier';
    $sql_params_translation = [
        ':identifier' => $identifier
    ];
    $translation = DB::fromDatabase($sql_translation,'@simple',$sql_params_translation);

    return $translation;
}

function getMailsFrom(){
    $sql = 'SELECT * FROM mails_from';
    $mails = DB::fromDatabase($sql,'@raw');
    if (!empty($mails)){
        echo '<table class="mails_from_table"><thead><th width="95%"></th><th></th></thead><tbody>';

        foreach ($mails as $key => $value){
            echo '<tr class="mt-1">';
            echo '<td class="text-left">'.$value['mail'].'<td>';
            echo '<td><button type="button" class="btn btn-danger delete_btn delete_mailfrom" data-id_mailfrom="'.$value['id'].'"><i class="fa fa-trash" aria-hidden="true"></i></button><td>';
        }
        echo '</tbody></table>';
    }else{
        echo translate('settings_mails_missing');
    }
}

function fg_getText($id_form_element){
    $sql = "SELECT * FROM translations WHERE id_form_elements=:id_form_elements";
    $sql_params = [
        ':id_form_elements' => $id_form_element
    ];
    $translations = DB::fromDatabase($sql, '@line', $sql_params);

    $sql = 'SELECT * FROM languages WHERE active = 1';
    $languages = DB::fromDatabase($sql,'@raw');
    echo '<table class="mt-3 w-100">';
    foreach ($languages as $key => $value){
        $json = json_decode($translations[$value['language_code']],true);

        echo '<tr>';
        echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
        echo '<td><input name="'.SslCrypt::encrypt('fg_edit_select'.$id_form_element).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" value="'.$json['text'].'"></td>';
        echo '</tr>';
    }
    echo '</table></div>';
}

function fg_getInputText($id_form_element){
    $sql = "SELECT * FROM translations WHERE id_form_elements=:id_form_elements";
    $sql_params = [
        ':id_form_elements' => $id_form_element
    ];
    $translations = DB::fromDatabase($sql, '@line', $sql_params);

    $sql = 'SELECT * FROM languages WHERE active = 1';
    $languages = DB::fromDatabase($sql,'@raw');
    echo '<table class="mt-3 w-100">';
    foreach ($languages as $key => $value){
        $json = json_decode($translations[$value['language_code']],true);
        echo '<tr>';
        echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
        echo '<td><input name="'.SslCrypt::encrypt('fg_edit_select'.$id_form_element).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" value="'.$json['label'].'"></td>';
        echo '</tr>';
    }
    echo '</table></div>';
}

function fg_getInputText_multiple($id_form_element){
    $sql = "SELECT * FROM translations WHERE id_form_elements=:id_form_elements";
    $sql_params = [
        ':id_form_elements' => $id_form_element
    ];
    $translations = DB::fromDatabase($sql, '@line', $sql_params);

    $sql = 'SELECT * FROM languages WHERE active = 1';
    $languages = DB::fromDatabase($sql,'@raw');
    echo '<table class="mt-3 w-100">';
    foreach ($languages as $key => $value){
        $json = json_decode($translations[$value['language_code']],true);
        echo '<tr>';
        echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
        echo '<td><input name="'.SslCrypt::encrypt('fg_edit_select'.$id_form_element).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" value="'.$json['label'].'"></td>';
        echo '</tr>';
    }
    echo '</table></div>';
}

function fg_getRating($id_form_element){
    $sql = "SELECT * FROM translations WHERE id_form_elements=:id_form_elements";
    $sql_params = [
        ':id_form_elements' => $id_form_element
    ];
    $translations = DB::fromDatabase($sql, '@line', $sql_params);

    $sql = 'SELECT * FROM languages WHERE active = 1';
    $languages = DB::fromDatabase($sql,'@raw');
    echo '<table class="mt-3 w-100">';
    foreach ($languages as $key => $value){
        $json = json_decode($translations[$value['language_code']],true);
        echo '<tr>';
        echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
        echo '<td><input name="'.SslCrypt::encrypt('fg_edit_select'.$id_form_element).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" value="'.$json['label'].'"></td>';
        echo '</tr>';
    }
    echo '</table></div>';
}

function fg_getRating10($id_form_element){
    $sql = "SELECT * FROM translations WHERE id_form_elements=:id_form_elements";
    $sql_params = [
        ':id_form_elements' => $id_form_element
    ];
    $translations = DB::fromDatabase($sql, '@line', $sql_params);

    $sql = 'SELECT * FROM languages WHERE active = 1';
    $languages = DB::fromDatabase($sql,'@raw');
    echo '<table class="mt-3 w-100">';
    foreach ($languages as $key => $value){
        $json = json_decode($translations[$value['language_code']],true);
        echo '<tr>';
        echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
        echo '<td><input name="'.SslCrypt::encrypt('fg_edit_select'.$id_form_element).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" value="'.$json['label'].'"></td>';
        echo '</tr>';
    }
    echo '</table></div>';
}