<?php
//  Parent-child Options
//  Support: support@liveopencart.com / Поддержка: help@liveopencart.ru

// Heading
$_['module_name']           = 'Родительские опции';
$_['heading_title']         = 'LIVEOPENCART: '.$_['module_name'];
$_['text_edit']             = 'Настройки модуля: '.$_['module_name'].'';

// Text
$_['text_module']           = 'Модули';
$_['text_success']          = 'Настройки обновлены!';
$_['text_content_top']      = 'Верх страницы';
$_['text_content_bottom']   = 'Низ страницы';
$_['text_column_left']      = 'Левая колонка';
$_['text_column_right']     = 'Правая колонка';
$_['text_m_all_options']    = 'Все доступные опции';
$_['text_m_support']       = "Разработка: <a href='https://liveopencart.ru' target='_blank'>liveopencart.ru</a> | Поддержка, вопросы и предложения: <a href=\"mailto:help@liveopencart.ru\">help@liveopencart.ru</a>";

// for the product edit page
$_['pcop_entry_settings']               = 'Parent options';
$_['pcop_entry_no_parent_options']      = 'Currently, there\'s no parent options.';
$_['pcop_entry_add_parent_option']      = 'Add parent option';
$_['pcop_entry_or']                     = 'ИЛИ';
$_['pcop_entry_remove_parent_option']   = 'Remove parent option';


// Entry
$_['entry_settings']                  = 'Настройки модуля';
$_['entry_disable_highlight']         = 'Отключить подсветку';
$_['entry_disable_highlight_help']    = 'Отключить цветовое выделение для родительских-дочерних опций на странице редактирования товара';

$_['error_xlsx_lib_is_not_found']     = 'Библиотека %s для работы с XLSX не найдена (необходима только использовая функций экспорта-импорта).';
$_['error_php_excel_is_necessary_for_xls']         = ' (библиотека PHPExcel необходима для импорта XLS) ';
//$_['entry_PHPExcel_not_found']        = 'Не установлена библиотека <a href="https://liveopencart.ru/news_site/phpexcel/" target="_blank" title="Что такое PHPExcel? Как установить PHPExcel?">PHPExcel</a>. Не найден файл: ';
$_['entry_export']                    = 'Экспорт';
$_['entry_export_description']        = 'Данные выгружаются в формате XLS.<br>В первой строке таблицы содержатся заголовки, в последующих строках данные';
$_['entry_export_get_file']           = 'Получить файл';
$_['entry_export_check_all']          = 'Отметить все';
$_['entry_export_fields']             = 'Выгружаемые данные:';
$_['entry_import']                    = 'Импорт';
$_['entry_import_ok']                 = 'Импорт завершен';
$_['entry_import_description']        = '
Import file format: XLSX. Import uses only first sheet for getting data.
<br>Первая строка таблицы должна содержать имена полей (заголовок): product_id, option_id, parent_option_id, parent_option_values_ids, pcop_or.
<br>Следующие строки таблицы должны содержать имена соответствующих полей данных в первой строке таблицы (parent_option_values_ids должен быть отделен ",").
';
$_['button_install_xlsx_lib']    	  = 'Установить %s автоматически';
$_['success_install_xlsx_lib']    	  = 'Библиотека %s установлена. Пожалуйста, перезагрузите страницу.';
$_['button_upload']		              = 'Импортировать файл';
$_['button_upload_help']              = 'импорт начинается сразу после выбора файла';
$_['entry_server_response']           = 'Ответ сервера:';
$_['entry_import_result']             = 'Обработанные продукты / опции';
$_['entry_import_delete_yes']         = 'Удалить существующие данные родительских и дочерних параметров перед импортом';
$_['entry_import_delete_no']          = 'Добавить новые данные родительских и дочерних опций в существующие';
$_['entry_use_po_ids']          			= 'Используйте <b> product_option_id </b> и <b> product_option_value_id </b> вместо <b> option_id </b> и <b> option_value_id</b>';
$_['entry_use_po_ids_help']      			= 'Может быть полезно, если один и тот же параметр используется дважды или более для каждого продукта (не будет работать, если product_option_id или product_option_value_id изменились по какой-либо причине между экспортом и импортом)';


$_['entry_m_version']                 = 'Версия Родительских опций';

$_['text_update_alert']               = '(доступна новая версия)';

$_['text_extension_page']             = '<a href="https://liveopencart.ru/opencart-moduli-shablony/moduli/opcii/roditelskie-optsii-3-dlya-opencart-3" target="_blank" title="Родительские опции на liveopencart.ru">Родительские опции на liveopencart.ru</a>';

$_['entry_about']               = 'Об авторах';
$_['module_description']    = '
Модуль предназначен для отображения / скрытия дочерних параметров (групп параметров) в зависимости от выбранных значений родительских параметров.';

$_['text_conversation'] = 'Есть вопросы по работе модуля? Требуется интеграция с шаблоном или доработка? Пишите: <b>help@liveopencart.ru</b>.';

$_['entry_we_recommend'] = 'Мы также рекомендуем:';
$_['text_we_recommend'] 							= '

';
$_['module_copyright'] = 'Модуль "'.$_['module_name'].'" это коммерческое дополнение. Не выкладывайте его на сайтах для скачивания и не передавайте его копии другим лицам.<br>
Приобретая модуль, Вы приобретаете право его использования на одном сайте. <br>Если Вы хотите использовать модуль на нескольких сайтах, следует приобрести отдельную копию модуля для каждого сайта.<br>';

$_['error_modificaton']           = 'Внимание: '.$_['module_name'].' модификация (OCMOD) не применяется!';
$_['error_permission']            = 'У Вас нет прав для доступа к модулю!';
?>