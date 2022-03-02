<?php
require_once('./../inc/first.php');
?>

<?php
ob_start();

error_reporting(E_ALL);
ini_set("display_errors","On");
require_once ('./../vendor/autoload.php');

// Increase time limit
set_time_limit(600);
ini_set('max_input_time',600);

// Increase memory limit
ini_set('memory_limit', '1024M');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
?>
<body>

<div class="verlauf p-3">
    <img class="header_img rounded mx-auto d-block mb-3 mt-3" src="./../img/keyvisual.jpg">
</div>
<?php
$error = false;
$showLoginForm = true;
$showExportForm = false;

//check if signed in user is allowed
if (!empty($_SESSION['user_id'])){
    $sql = 'SELECT * FROM users WHERE id=:id';
    $params = [
        ':id' => $_SESSION['user_id']
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);

    if (!empty($userData) && $userData['backend_mail'] == 1) {
        $showLoginForm = false;
        $showExportForm = true;
    }
}

if (!empty($_POST) && empty($_POST['download'])) {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);

    $sql = 'SELECT * FROM users WHERE username=:username';
    $params = [
        ':username' => $username
    ];
    $userData = DB::fromDatabase($sql,'@line', $params);

    if (!empty($userData) && $password === $userData['password'] && $userData['backend_mail'] == 1) {
        $showLoginForm = false;
        $showExportForm = true;
    } else {
        $error = true;
    }
}

if (!empty($_POST) && !empty($_POST['download'])) {
    $showLoginForm = false;
    $showExportForm = true;
}

if ($showLoginForm){
    ?>
    <div class="container">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4 text-center">
                <form method="post">
                    <input class="login form-control-lg" type="text" name="username" placeholder="Benutzername"/>
                    <br />
                    <input class="login form-control-lg" type="password" name="password" placeholder="Passwort"/>
                    <br />
                    <button type="submit" class="btn btn-primary w-100 mt-4">Login</button>
                </form>
                <?php
                if ($error){
                    echo '<p class="alert alert-danger mt-3">Login fehlgeschlagen. Der Benutzername oder das Passwort ist fehlerhaft.</p>';
                }
                ?>
            </div>
            <div class="col-lg-4"></div>
        </div>
    </div>
    <?php
}

if ($showExportForm){
    $sql = 'SELECT * FROM `email_dispatches` ORDER BY sending_time DESC';
    $mail_dispatches = DB::fromDatabase($sql,'@raw');

    $sql = 'SELECT * FROM `languages` WHERE active=1';
    $languages = DB::fromDatabase($sql,'@raw');

    ?>
    <div class="container">
        <div class="row">
            <div class="col-lg-4"></div>
            <div class="col-lg-4 text-center">
                <form method="post">
                    <p class="form-label fw-bold mt-2">Sprache der Fragen</p>
                    <select class="form-select mb-3" name="language" required>
                        <option selected disabled value=""><?= translate('select_standard')?></option>
                        <?php
                            foreach ($languages as $key => $value){
                                echo '<option value="'.$value['language_code'].'">'.$value['language'].'</option>';
                            }
                        ?>
                    </select>

                    <p class="form-label fw-bold">Versände</p>
                    <?php
                    foreach ($mail_dispatches as $key => $value){

                        $formname = '';
                        if (!empty($value['name'])){
                            $formname = ' '.$value['name'];
                        }

                        $date = new DateTime($value['sending_time']);
                        $dateFormatted = $date->format('d.m.Y | H:i');
                        echo '<input class="checkbox_auswertung" type="checkbox" id="mail_dispatch'.$value['id'].'" name="'.SslCrypt::encrypt('mail_dispatch').'[]" value="'.$value['id'].'">';
                        echo '<label class="float-start" for="mail_dispatch'.$value['id'].'">'.$dateFormatted.$formname.'</label><br/>';
                    }
                    ?>

                    <button type="submit" class="btn btn-primary w-100 mt-4 mb-3">Antworten herunterladen</button>
                    <input type="hidden" name="download" value="1">
                </form>
            </div>
            <div class="col-lg-4"></div>
        </div>
    </div>
    <?php
}

if ($showExportForm && !empty($_POST['mail_dispatch'])){
    $multiple = false;

    if (count($_POST['mail_dispatch'])>1){
        $multiple = true;
        // TODO multiple
        die('WIP');
        $id_mail_dispatch = implode(',',$groupsArray);
    }else{
        $id_mail_dispatch = $_POST['mail_dispatch'][0];
    }

    $sql = 'SELECT DISTINCT id_groups FROM mail_dispatches2groups WHERE id_mail_dispatches IN ('.$id_mail_dispatch.')';
    $groupsArray = DB::fromDatabase($sql,'@array');

    if (!empty($groupsArray)){
        $groups = implode(',',$groupsArray);
    }else{
        //TODO ERROR
        die('NO ATTENDEES');
    }

    // Excel properties
    $properties = [
        'Creator' => 'BWM',
        'Title' => 'Auswertung Feedback-Umfrage',
    ];

    // Excel default font config
    $fontProperties = [
        'Name' => 'Arial',
        'Size' => 10,
    ];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $language = '`'.$_POST['language'].'`';

    if ($multiple){
        $sql = 'select
`nasskapp`.`id_form_elements` AS `id_form_elements`,
`nasskapp`.`answer` AS `answer`,`nasskapp`.`id_groups` AS `id_groups`,
(`nasskapp`.`anteil` / `nasskapp`.`alle`) AS `proportion`,
`nasskapp`.`anteil` AS `amount`,
(select `form_elements`.`is_text` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)) AS `is_text`,
(select `form_elements`.`order` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)) AS `order`,
(select `e`.'.$language.' from `translations` `e` where (`nasskapp`.`id_form_elements` = `e`.`id_form_elements`)) AS `de`,
`nasskapp`.`alle` AS `amount_element`,
(select round(avg(`answers`.`answer`),2) from `answers` where (((select `attendees`.`id_groups` from `attendees` where (`attendees`.`id` = `answers`.`id_attendees`)) IN ('.$groups.')) and (`answers`.`id_form_elements` = `nasskapp`.`id_form_elements`))) AS `avg`,
(SELECT count(id) FROM answers AS x5 WHERE nasskapp.id_form_elements=x5.id_form_elements AND x5.answer=nasskapp.answer AND id_attendees IN (SELECT id FROM attendees WHERE id_groups IN ('.$groups.'))) AS this_answer,
(SELECT count(id) FROM answers AS x0 WHERE nasskapp.id_form_elements=x0.id_form_elements AND id_attendees IN (SELECT id FROM attendees WHERE id_groups IN ('.$groups.'))) AS x0



from (select `kapp`.`id` AS `id`,`kapp`.`id_form_elements` AS `id_form_elements`,`kapp`.`id_attendees` AS `id_attendees`,`kapp`.`answer` AS `answer`,`kapp`.`id_groups` AS `id_groups`,(select count(0) from `answers` `c` where ((`c`.`id_form_elements` = `kapp`.`id_form_elements`) and (`kapp`.`id_groups` = (select `attendees`.`id_groups` from `attendees` where (`attendees`.`id` = `c`.`id_attendees`))))) AS `alle`,(select count(0) from `answers` `d` where ((`d`.`id_form_elements` = `kapp`.`id_form_elements`) and (`kapp`.`id_groups` = (select `attendees`.`id_groups` from `attendees` where (`attendees`.`id` = `d`.`id_attendees`))) and (`kapp`.`answer` = `d`.`answer`))) AS `anteil` from (select `b`.`id` AS `id`,`b`.`id_form_elements` AS `id_form_elements`,`b`.`id_attendees` AS `id_attendees`,`b`.`answer` AS `answer`,(select `a`.`id_groups` from `attendees` `a` where (`a`.`id` = `b`.`id_attendees`)) AS `id_groups` from `answers` `b` WHERE id_attendees IN (SELECT id FROM attendees WHERE id_groups IN ('.$groups.')) group by `b`.`id_form_elements`,`b`.`answer`,`id_groups`) `kapp`) `nasskapp` order by (select `form_elements`.`order` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)),`nasskapp`.`id_form_elements`,`nasskapp`.`answer`';

    }else{

        /*$sql = 'select
`nasskapp`.`id_form_elements` AS `id_form_elements`,
`nasskapp`.`answer` AS `answer`,`nasskapp`.`id_groups` AS `id_groups`,
(`nasskapp`.`anteil` / `nasskapp`.`alle`) AS `proportion`,
`nasskapp`.`anteil` AS `amount`,
(select `form_elements`.`is_text` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)) AS `is_text`,
(select `form_elements`.`order` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)) AS `order`,
(select `e`.'.$language.' from `translations` `e` where (`nasskapp`.`id_form_elements` = `e`.`id_form_elements`)) AS `de`,
`nasskapp`.`alle` AS `amount_element`,
(select round(avg(`answers`.`answer`),2) from `answers` where (((select `attendees`.`id_groups` from `attendees` where (`attendees`.`id` = `answers`.`id_attendees`)) IN ('.$groups.')) and (`answers`.`id_form_elements` = `nasskapp`.`id_form_elements`))) AS `avg`,
(SELECT count(id) FROM answers AS x5 WHERE nasskapp.id_form_elements=x5.id_form_elements AND x5.answer=nasskapp.answer AND id_attendees IN (SELECT id FROM attendees WHERE id_groups IN ('.$groups.'))) AS this_answer,
(SELECT count(id) FROM answers AS x0 WHERE nasskapp.id_form_elements=x0.id_form_elements AND id_attendees IN (SELECT id FROM attendees WHERE id_groups IN ('.$groups.'))) AS x0



from (select `kapp`.`id` AS `id`,`kapp`.`id_form_elements` AS `id_form_elements`,`kapp`.`id_attendees` AS `id_attendees`,`kapp`.`answer` AS `answer`,`kapp`.`id_groups` AS `id_groups`,(select count(0) from `answers` `c` where ((`c`.`id_form_elements` = `kapp`.`id_form_elements`) and (`kapp`.`id_groups` = (select `attendees`.`id_groups` from `attendees` where (`attendees`.`id` = `c`.`id_attendees`))))) AS `alle`,(select count(0) from `answers` `d` where ((`d`.`id_form_elements` = `kapp`.`id_form_elements`) and (`kapp`.`id_groups` = (select `attendees`.`id_groups` from `attendees` where (`attendees`.`id` = `d`.`id_attendees`))) and (`kapp`.`answer` = `d`.`answer`))) AS `anteil` from (select `b`.`id` AS `id`,`b`.`id_form_elements` AS `id_form_elements`,`b`.`id_attendees` AS `id_attendees`,`b`.`answer` AS `answer`,(select `a`.`id_groups` from `attendees` `a` where (`a`.`id` = `b`.`id_attendees`)) AS `id_groups` from `answers` `b` WHERE id_attendees IN (SELECT id FROM attendees WHERE id_groups IN ('.$groups.')) group by `b`.`id_form_elements`,`b`.`answer`,`id_groups`) `kapp`) `nasskapp` order by (select `form_elements`.`order` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)),`nasskapp`.`id_form_elements`,`nasskapp`.`answer`';
*/
        $sql = 'select
`nasskapp`.`id_form_elements` AS `id_form_elements`,
`nasskapp`.`answer` AS `answer`,`nasskapp`.`id_groups` AS `id_groups`,
(`nasskapp`.`anteil` / `nasskapp`.`alle`) AS `proportion`,
`nasskapp`.`anteil` AS `amount`,

(select `form_element_types`.`is_text` from `form_element_types` where (`form_element_types`.`id` = (SELECT id_form_element_types FROM form_elements WHERE id=`nasskapp`.`id_form_elements`))) AS `is_text`,
(select `form_elements`.`order` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)) AS `order`,
(select `e`.'.$language.' from `translations` `e` where (`nasskapp`.`id_form_elements` = `e`.`id_form_elements`)) AS `de`,
`nasskapp`.`alle` AS `amount_element`,
(select round(avg(`answers`.`answer`),2) from `answers` where (((select `persons`.`id_groups` from `persons` where (`persons`.`id` = `answers`.`id_persons`)) IN ('.$groups.')) and (`answers`.`id_form_elements` = `nasskapp`.`id_form_elements`))) AS `avg`,
(SELECT count(id) FROM answers AS x5 WHERE nasskapp.id_form_elements=x5.id_form_elements AND x5.answer=nasskapp.answer AND id_persons IN (SELECT id FROM persons WHERE id_groups IN ('.$groups.'))) AS this_answer,
(SELECT count(id) FROM answers AS x0 WHERE nasskapp.id_form_elements=x0.id_form_elements AND id_persons IN (SELECT id FROM persons WHERE id_groups IN ('.$groups.'))) AS x0



from (select `kapp`.`id` AS `id`,`kapp`.`id_form_elements` AS `id_form_elements`,`kapp`.`id_persons` AS `id_persons`,`kapp`.`answer` AS `answer`,`kapp`.`id_groups` AS `id_groups`,(select count(0) from `answers` `c` where ((`c`.`id_form_elements` = `kapp`.`id_form_elements`) and (`kapp`.`id_groups` = (select `persons`.`id_groups` from `persons` where (`persons`.`id` = `c`.`id_persons`))))) AS `alle`,(select count(0) from `answers` `d` where ((`d`.`id_form_elements` = `kapp`.`id_form_elements`) and (`kapp`.`id_groups` = (select `persons`.`id_groups` from `persons` where (`persons`.`id` = `d`.`id_persons`))) and (`kapp`.`answer` = `d`.`answer`))) AS `anteil` from (select `b`.`id` AS `id`,`b`.`id_form_elements` AS `id_form_elements`,`b`.`id_persons` AS `id_persons`,`b`.`answer` AS `answer`,(select `a`.`id_groups` from `persons` `a` where (`a`.`id` = `b`.`id_persons`)) AS `id_groups` from `answers` `b` WHERE id_persons IN (SELECT id FROM persons WHERE id_groups IN ('.$groups.')) group by `b`.`id_form_elements`,`b`.`answer`,`id_groups`) `kapp`) `nasskapp` order by (select `form_elements`.`order` from `form_elements` where (`form_elements`.`id` = `nasskapp`.`id_form_elements`)),`nasskapp`.`id_form_elements`,`nasskapp`.`answer`';
    }
    $answers = DB::fromDatabase($sql,'@raw');

    //Excel mit Daten füllen
    if ($language=='`de`')
    {
        $arrayData = [
            ['Frage', 'Antwort', 'Anzahl', 'Anteil'],
        ];
    }
    else
    {
        $arrayData = [
            ['Question', 'Answer', 'Amount', 'Percentage'],
        ];
    }


    // Border festlegen
    $thinBorderArray = array(
        'borders' => array(
            'bottom' => array(
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ),
        ),
    );

    $entries = 1;

    $previousId = '';
    $previousIdQuestion = '';
    if (empty($answers)){
        $arrayData[] = ['Für diese Gruppe(n) gibt es noch keine Antworten'];
    }else {
        $question_nr = 1;
        foreach ($answers as $key => $value) {
            //file_put_contents(__DIR__.'/debug2.txt',time().' - '.$key . "\n",FILE_APPEND);

            if ($previousId == $value['id_form_elements'] && $previousAnswer == $value['answer']){
                continue;
            }

            if ($previousId !== $value['id_form_elements']) {

                if ($entries != 1 && $saved_avg>0 && !$saved_is_text){
                    if ($language=='`de`')
                    {
                        $arrayData[] = ['','Durchschnittliche Antwort:',$saved_avg];
                    }
                    else
                    {
                        $arrayData[] = ['','Avarage:',$saved_avg];
                    }

                    $sheet->getStyle('A' . $entries . ':D' . $entries)->applyFromArray($thinBorderArray);
                    $entries++;
                    $sheet->getStyle('A' . $entries . ':D' . $entries)->applyFromArray($thinBorderArray);
                }

                $question_array = json_decode($value['de'], true);

                if (!empty($question_array['id_question']) && $previousIdQuestion !== $question_array['id_question']){
                    $previousIdQuestion = $question_array['id_question'];
                    $sql = 'SELECT '.$language.' FROM translations WHERE id_form_elements=:id_form_elements';
                    $params = [
                        ':id_form_elements' => $question_array['id_question']
                    ];
                    $question_extra = DB::fromDatabase($sql,'@line',$params);
                    $question_extra_array = json_decode($question_extra['de'], true);
                    if (!empty($question_extra_array['header'])){
                        if (preg_match('/@number@/',$question_extra_array['header'])){
                            $question_extra_array['header'] = preg_replace('/@number@/',$question_nr.'. ',$question_extra_array['header']);
                            $question_nr++;
                        }

                        $arrayData[] = [$question_extra_array['header']];
                        $entries++;
                    }
                    if (!empty($question_extra_array['text'])){
                        if (preg_match('/@number@/',$question_extra_array['text'])){
                            $question_extra_array['text'] = preg_replace('/@number@/',$question_nr.'. ',$question_extra_array['text']);
                            $question_nr++;
                        }

                        $arrayData[] = [$question_extra_array['text']];
                        $entries++;
                    }
                }

                $sheet->getStyle('A' . $entries . ':D' . $entries)->applyFromArray($thinBorderArray);

                $question = $question_array['label'];

            } else {
                $question = '';
            }
            $previousId = $value['id_form_elements'];
            $previousAnswer = $value['answer'];

            $amount = '';
            $proportion = '';

            if ($value['is_text'] == 0){
                $amount = $value['amount'];
                $proportion = $value['proportion'];
            }

            if (preg_match('/@number@/',$question)){
                $question = preg_replace('/@number@/',$question_nr.'. ',$question);
                $question_nr++;
            }

            if ($multiple){
                if ($value['is_text'] == 0){
                    $amount = $value['this_answer'];
                    $proportion = $value['this_answer'] / $value['x0'];
                }else{
                    $amount = '';
                    $proportion = '';
                }
            }

            $arrayData[] = [$question, $value['answer'], $amount, $proportion];

            $saved_avg = $value['avg'];

            $saved_is_text = $value['is_text'];

            $entries++;
        }
        //file_put_contents(__DIR__.'/debug2.txt',time().' foreach done ' . "\n",FILE_APPEND);

    }

    $spreadsheet->getActiveSheet()
        ->fromArray(
            $arrayData,
            NULL
        );

    // Zellen formatieren
    $styleHeader = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'argb' => '0b6cfb',
            ],
        ],
    ];
    $sheet->getStyle('A1:D1')->applyFromArray($styleHeader);

    $styleData = [
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
    ];
    $sheet->getStyle('B2:D'.$entries)->applyFromArray($styleData);

    foreach(['A','B'] as $columnID) {
        $sheet->getColumnDimension($columnID)
            ->setWidth(120);
        $sheet->getStyle($columnID)->getAlignment()->setWrapText(true);
    }
    foreach(['C','D'] as $columnID) {
        $sheet->getColumnDimension($columnID)
            ->setAutoSize(true);
    }

    // Border festlegen
    $thinBorderArray = array(
        'borders' => array(
            'allBorders' => array(
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ),
        ),
    );
    $sheet->getStyle('A1:D1')->applyFromArray($thinBorderArray);

    // Format festlegen
    $formatArray = array(
        'numberFormat' => [
            'formatCode' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00
        ]
    );
    $sheet->getStyle('D1:D'.$entries)->applyFromArray($formatArray);

    //file_put_contents(__DIR__.'/debug2.txt',time().' before writer ' . "\n",FILE_APPEND);

    $writer = new Xlsx($spreadsheet);
    $file_path = './../temp/auswertung.xlsx';
    $writer->save($file_path);

    //file_put_contents(__DIR__.'/debug2.txt',time().' after writer done ' . "\n",FILE_APPEND);

    if (!$multiple){
        $sql = 'SELECT * FROM `email_dispatches` WHERE id=:id';
        $params = [
            ':id' => $id_mail_dispatch
        ];
        $mail_dispatchData = DB::fromDatabase($sql,'@raw', $params);

        $date = new DateTime($groupData['sending_time']);
        $dateFormattedFilename = $date->format('Y_m_d-H:i');
    }else{
        $participation_type = 'mehrere_events';
        $date = new DateTime();
        $dateFormattedFilename = $date->format('Y_m_d-H:i');
    }

    header('location: ./xlsx.php?name='.$mail_dispatchData['name'].'_'.$dateFormattedFilename);
}
?>
<script src="./../js/jquery.min.js"></script>
<script src="./../js/bootstrap.min.js"></script>
<script src="./../js/script.js"></script>
</body>
</html>