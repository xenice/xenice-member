// assets/js/xenice-member-editor.js
jQuery(document).ready(function($) {
    if ($('.xm-user-select').length > 0) {
        $('.xm-user-select').select2({
            width: '300px',
            placeholder: xeniceMemberEditor.select2Placeholder || 'Search user…'
        });
    }
});