<?

/** Special page for access denied error
 * @package errors
 */

echo $renderer->heading($ren->trans('core_access_denied'));
Tag::p(array("class" => 'advice desc', "content" => $ren->trans('core_access_denied_text')));
