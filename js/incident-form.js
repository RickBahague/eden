(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.edenIncidentForm = {
    attach: function (context, settings) {
      // Handle unspecified date checkbox
      $('input[name="unspecified_date[value]"]', context).once('eden-incident-form').each(function () {
        $(this).on('change', function () {
          var dateField = $('input[name="date_of_incident[0][value]"]');
          if ($(this).is(':checked')) {
            dateField.prop('disabled', true).val('');
          } else {
            dateField.prop('disabled', false);
          }
        });
      });

      // Handle incident continuing checkbox visibility
      $('input[name="unspecified_date[value]"]', context).on('change', function () {
        var continuingField = $('input[name="incident_continuing[value]"]').closest('.form-item');
        if ($(this).is(':checked')) {
          continuingField.hide();
        } else {
          continuingField.show();
        }
      });
    }
  };

})(jQuery, Drupal); 