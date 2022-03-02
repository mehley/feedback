<?php
session_start();
require_once('./../inc/includes.php');

switch ($_POST['action']){
    case 'select_element':
        echo '<li><div class="fg_select_div'.$_POST['numberElement'].'"><hr>
            <div class="float-start fg_move margin_right_10"><i class="fas fa-arrows-alt"></i></div>
            <select class="fg_element_select form-generator-select form-select-md mt-2 d-inline-block" data-number_element="'.$_POST['numberElement'].'" 
            name="'.SslCrypt::encrypt('fg_select'.$_POST['numberElement']) .'[type]">
                <option value="" selected disabled>'.translate('select_standard').'</option>';
        $sql = "SELECT * FROM form_element_types";
        $formElementTypes = DB::fromDatabase($sql,'@raw');
        foreach ($formElementTypes as $key => $value){
            echo '<option value="'.$value['type'].'">'.translate($value['type']).'</option>';
        }
        echo '
            </select> 
            <button type="button" class="d-inline-block addElementBtn fg_deleteSelect fg-deleteBTN margin_left_10" data-number_element="'.$_POST['numberElement'].'" title="'.translate('fg_remove_element').'"><i class="fas fa-times-circle"></i></button>
            <br>
            <div class="fg_input_div'.$_POST['numberElement'].'"></div></div>';
        break;
    case 'text':
        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        echo '<table class="mt-3 w-100">';
        foreach ($languages as $key => $value){
            echo '<tr>';
            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
            echo '<td><input name="'.SslCrypt::encrypt('fg_select'.$_POST['numberElement']).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" placeholder="'.translate('fg_text').'"></td>';
            echo '</tr>';
        }
        echo '</table></li>';
        break;
    case 'inputText':
        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        echo '<table class="mt-3 w-100">';
        foreach ($languages as $key => $value){
            echo '<tr>';
            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
            echo '<td><input name="'.SslCrypt::encrypt('fg_select'.$_POST['numberElement']).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" placeholder="'.translate('fg_inputText').'"></td>';
            echo '</tr>';
        }
        echo '</table></li>';
        break;
    case 'inputText_multiple':
        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        echo '<table class="mt-3 w-100">';
        foreach ($languages as $key => $value){
            echo '<tr>';
            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
            echo '<td><input name="'.SslCrypt::encrypt('fg_select'.$_POST['numberElement']).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" placeholder="'.translate('fg_inputText_multiple').'"></td>';
            echo '</tr>';
        }
        echo '</table></li>';
        break;
    case 'rating':
        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        echo '<table class="mt-3 w-100">';
        foreach ($languages as $key => $value){
            echo '<tr>';
            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
            echo '<td><input name="'.SslCrypt::encrypt('fg_select'.$_POST['numberElement']).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" placeholder="'.translate('fg_rating').'"></td>';
            echo '</tr>';
        }
        echo '</table></li>';
        break;
    case 'rating10':
        $sql = 'SELECT * FROM languages WHERE active = 1';
        $languages = DB::fromDatabase($sql,'@raw');
        echo '<table class="mt-3 w-100">';
        foreach ($languages as $key => $value){
            echo '<tr>';
            echo '<td class="w_48px"><img src="./../img/fg_flags/'.$value['language_code'].'.png" class="form-generator-flag"></td>';
            echo '<td><input name="'.SslCrypt::encrypt('fg_select'.$_POST['numberElement']).'['.$value['language_code'].']" type="text" class="form-control-check mt-2 form-control" placeholder="'.translate('fg_rating10').'"></td>';
            echo '</tr>';
        }
        echo '</table></li>';
        break;
    case 'loadForm':
        $sql = 'SELECT * FROM forms WHERE id=:id';
        $sql_params = [
            ':id' => $_POST['id']
        ];
        $form = DB::fromDatabase($sql,'@line',$sql_params);

        echo '<input class="mt-4 form-control" type="text" name="'.SslCrypt::encrypt('form_name').'" value="'.$form['name'].'" required/>';
        echo '<ul id="sortable" class="fg_list">';

        $sql = 'SELECT * FROM form_elements WHERE id_forms = :id_forms ORDER BY `order` ASC';
        $sql_params = [
            ':id_forms' => $_POST['id']
        ];
        $formElements = DB::fromDatabase($sql,'@raw', $sql_params);

        foreach ($formElements as $keyA => $valueA){
            echo '<li>';
            $sql = 'SELECT * FROM form_element_types WHERE id = :id';
            $sql_params = [
                ':id' => $valueA['id_form_element_types']
            ];
            $formElementType = DB::fromDatabase($sql,'@line', $sql_params);

            $sql = "SELECT * FROM form_element_types";
            $formElementTypes = DB::fromDatabase($sql,'@raw');

            echo '<div class="fg_select_div'.$valueA['id'].'"><hr>
            <div class="float-start fg_move margin_right_10"><i class="fas fa-arrows-alt"></i></div>
            <select class="fg_element_select form-generator-select form-select-md mt-2 d-inline-block" data-number_element="'.$valueA['id'].'" 
            name="'.SslCrypt::encrypt('fg_edit_select'.$valueA['id']) .'[type]">';

            foreach ($formElementTypes as $keyB => $valueB){
                if($formElementType['id'] == $valueB['id']){
                    echo '<option value="'.$valueB['type'].'" selected>'.translate($valueB['type']).'</option>';
                }else{
                    echo '<option value="'.$valueB['type'].'">'.translate($valueB['type']).'</option>';
                }
            }
            echo '
            </select> 
            <button type="button" class="d-inline-block addElementBtn fg_deleteSelect fg-deleteBTN margin_left_10" data-number_element="'.$valueA['id'].'" title="'.translate('fg_remove_element').'"><i class="fas fa-times-circle"></i></button>
            <br>
            <div class="fg_input_div'.$_POST['numberElement'].'"></div>';

            switch ($formElementType['type']){
                case 'text':
                    fg_getText($valueA['id']);
                    break;
                case 'inputText':
                    fg_getInputText($valueA['id']);
                    break;
                case 'inputText_multiple':
                    fg_getInputText_multiple($valueA['id']);
                    break;
                case 'rating':
                    fg_getRating($valueA['id']);
                    break;
                case 'rating10':
                    fg_getRating10($valueA['id']);
                    break;
                default:

                    break;
            }
            echo '</li>';
        }
        echo '</ul>';

        break;
    case 'removeFormConfirm':
        if ($_POST['id'] > 0){
            $translations = [
                'titleConfirm' => translate('swal_deleteForm_confirm_title'),
                'textConfirm' => translate('swal_deleteForm_confirm_text')
            ];

            echo json_encode($translations);
        }
        break;
    case 'removeForm':
        if ($_POST['id'] > 0){
            $sql = 'DELETE FROM forms WHERE id=:id LIMIT 1';
            $sql_params = [
                ':id' => $_POST['id']
            ];
            DB::toDatabase($sql, $sql_params);

            $translations = [
                'title' => translate('swal_deleteForm_title'),
                'text' => translate('swal_deleteForm_text'),
            ];

            echo json_encode($translations);
        }
        break;
    case 'removeSelectConfirm':
        if ($_POST['id'] > 0){

            $translations = [
                'title' => translate('swal_deleteSelect_title'),
                'text' => translate('swal_deleteSelect_text'),
            ];

            echo json_encode($translations);
        }
        break;
    case 'submitForm':
        if (!empty($_POST['form_name'])) {
            if (!empty($_POST['selectExistingForm'])){
                $sql = 'SELECT * FROM forms WHERE name=:name AND id!=:id';
                $sql_params = [
                    ':name' => $_POST['form_name'],
                    ':id' => $_POST['selectExistingForm'],
                ];

                $response['id_form'] = $_POST['selectExistingForm'];

            }else{
                $sql = 'SELECT * FROM forms WHERE name=:name';
                $sql_params = [
                    ':name' => $_POST['form_name']
                ];
            }

            $formName_check = DB::fromDatabase($sql, '@simple',$sql_params );

            if (empty($formName_check)){
                $order = 1;
                if (!empty($_POST['selectExistingForm']) && $_POST['selectExistingForm'] > 0) {
                    //Edit existing Form
                    $sql = "UPDATE forms SET `name` = :name WHERE id=:id";
                    $sql_params = [
                        ':name' => $_POST['form_name'],
                        ':id' => $_POST['selectExistingForm']
                    ];
                    DB::toDatabase($sql, $sql_params);

                    $response['response'] = 'success';
                    $response['title'] = translate('swal_saveForm_success_title');
                    $response['text'] = translate('swal_saveForm_success_text');

                    //handle formelements
                    $formelements = [];
                    foreach ($_POST as $key => $value) {
                        $needle = 'fg_edit_select';
                        if (strpos($key, $needle) === 0) {
                            $id_form_element = substr($key,strlen($needle));
                            $formelements[] = $id_form_element;

                            $sql = 'SELECT id,format_translation_json FROM form_element_types WHERE type=:type';
                            $sql_params = [
                                ':type' => $value['type']
                            ];
                            $element_type_data = DB::fromDatabase($sql, '@line', $sql_params);

                            $sql = "UPDATE form_elements SET id_form_element_types=:id_form_element_types,`order`=:order WHERE id=:id";
                            $sql_params = [
                                ':id_form_element_types' => $element_type_data['id'],
                                ':order' => $order,
                                ':id' => $id_form_element
                            ];
                            DB::toDatabase($sql, $sql_params);

                            //Handle translations
                            $sql = 'SELECT language_code FROM languages WHERE active=1';
                            $languages = DB::fromDatabase($sql, '@array');

                            foreach($value as $keyLanguage => $valueLanguage){
                                if (in_array($keyLanguage,$languages)){
                                    $sql = 'UPDATE translations SET ' . $keyLanguage . '=:' . $keyLanguage .' WHERE id_form_elements=:id_form_elements';

                                    //Create Json for translation
                                    $languageJson = preg_replace('/@text@/', $valueLanguage, $element_type_data['format_translation_json']);

                                    $sql_params = [
                                        ':id_form_elements' => $id_form_element,
                                        ':'.$keyLanguage => $languageJson
                                    ];

                                    DB::toDatabase($sql, $sql_params);
                                }
                            }
                            $order++;
                        }
                        if (strpos($key, 'fg_select') === 0) {

                            $sql = 'SELECT id,format_translation_json FROM form_element_types WHERE type=:type';
                            $sql_params = [
                                ':type' => $value['type']
                            ];
                            $element_type_data = DB::fromDatabase($sql, '@line', $sql_params);

                            $sql = 'INSERT INTO form_elements SET id_forms=:id_forms, `order`=:order,id_form_element_types=:id_form_element_types';
                            $sql_params = [
                                ':id_forms' => $_POST['selectExistingForm'],
                                ':order' => $order,
                                ':id_form_element_types' => $element_type_data['id']
                            ];
                            DB::toDatabase($sql, $sql_params);
                            $id_form_element = DB::lastInsertId();
                            $formelements[] = $id_form_element;

                            //Handle translations
                            $sql = 'SELECT language_code FROM languages WHERE active=1';
                            $languages = DB::fromDatabase($sql, '@raw');

                            $sql = 'INSERT INTO translations SET id_form_elements=:id_form_elements';
                            $sql_params = [];
                            $sql_params[':id_form_elements'] = $id_form_element;

                            foreach ($languages as $keyB => $valueB) {
                                $sql .= ', ' . $valueB['language_code'] . '=:' . $valueB['language_code'];
                                $value[$valueB['language_code']] = preg_replace('/@text@/', $value[$valueB['language_code']], $element_type_data['format_translation_json']);
                                $sql_params[':' . $valueB['language_code']] = $value[$valueB['language_code']];
                            }
                            DB::toDatabase($sql, $sql_params);
                            $order++;
                        }
                    }

                    $sql = 'DELETE FROM form_elements WHERE id_forms=:id_forms AND id NOT IN ('.implode(',',$formelements).')';
                    $sql_params = [
                        ':id_forms' => $_POST['selectExistingForm']
                    ];

                    DB::toDatabase($sql,$sql_params,false,true);

                } else {
                    $sql = "INSERT INTO forms SET `name` = :name";
                    $sql_params = [
                        ':name' => $_POST['form_name']
                    ];
                    DB::toDatabase($sql, $sql_params);

                    $id_form = DB::lastInsertId();

                    $response['id_form'] = $id_form;

                    //handle formelements
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'fg_select') === 0) {
                            $sql = 'SELECT id,format_translation_json FROM form_element_types WHERE type=:type';
                            $sql_params = [
                                ':type' => $value['type']
                            ];
                            $element_type_data = DB::fromDatabase($sql, '@line', $sql_params);

                            $sql = 'INSERT INTO form_elements SET id_forms=:id_forms, `order`=:order,id_form_element_types=:id_form_element_types';
                            $sql_params = [
                                ':id_forms' => $id_form,
                                ':order' => $order,
                                ':id_form_element_types' => $element_type_data['id']
                            ];
                            DB::toDatabase($sql, $sql_params);
                            $id_form_element = DB::lastInsertId();

                            $order++;

                            //Handle translations
                            $sql = 'SELECT language_code FROM languages WHERE active=1';
                            $languages = DB::fromDatabase($sql, '@raw');

                            $sql = 'INSERT INTO translations SET id_form_elements=:id_form_elements';
                            $sql_params = [];
                            $sql_params[':id_form_elements'] = $id_form_element;

                            foreach ($languages as $keyB => $valueB) {
                                $sql .= ', ' . $valueB['language_code'] . '=:' . $valueB['language_code'];
                                $value[$valueB['language_code']] = preg_replace('/@text@/', $value[$valueB['language_code']], $element_type_data['format_translation_json']);
                                $sql_params[':' . $valueB['language_code']] = $value[$valueB['language_code']];
                            }
                            DB::toDatabase($sql, $sql_params);
                        }
                    }
                }
                $response['response'] = 'success';
                $response['title'] = translate('swal_saveForm_success_title');
                $response['text'] = translate('swal_saveForm_success_text');
            }else{
                $response['response'] = 'usedName';
                $response['title'] = translate('swal_saveForm_usedName_title');
                $response['text'] = translate('swal_saveForm_usedName_text');
            }
        }else{
            $response['response'] = 'missingName';
            $response['title'] = translate('swal_saveForm_missingName_title');
            $response['text'] = translate('swal_saveForm_missingName_text');
        }
        echo json_encode($response);
        break;
    case 'form_selector':
        echo '<select class="form-select" id="selectExistingForm" name="'.SslCrypt::encrypt('selectExistingForm').'">';
        if ($_POST['id_form'] > 0){
            echo '<option value="" disabled>'.translate('selectExistingForm').'</option>';
        }else{
            echo '<option value="" selected disabled>'.translate('selectExistingForm').'</option>';
        }
        $sql = 'SELECT * FROM forms ORDER BY name ASC';
        $forms = DB::fromDatabase($sql,'@raw');
        foreach ($forms as $key => $value){
            if ($_POST['id_form'] == $value['id']){
                echo '<option value="'.$value['id'].'" selected>'. $value['name'] .'</option>';
            }else{
                echo '<option value="'.$value['id'].'">'. $value['name'] .'</option>';
            }
        }
        echo '</select>';

        break;
    default:

        break;
}