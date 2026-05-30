/* Dhivehi Writer — Admin JS */
/* Reserved for future admin enhancements (e.g. live font preview on settings page) */
jQuery(document).ready(function ($) {
    // Live preview of font change on settings page
    var $fontSelect = $('#dhw_font');
    var $preview = $('<p style="margin-top:10px;padding:8px;background:#f9f9f9;border:1px solid #ddd;direction:rtl;text-align:right;">ދިވެހި ލިޔުން — preview</p>');
    if ($fontSelect.length) {
        $fontSelect.after($preview);
        function updatePreview() {
            // Mirror the frontend stack: chosen font → bundled web font → fallbacks.
            $preview.css('font-family', "'" + $fontSelect.val() + "', 'Faruma', 'Noto Sans Thaana', serif");
        }
        $fontSelect.on('change', updatePreview);
        updatePreview();
    }
});
