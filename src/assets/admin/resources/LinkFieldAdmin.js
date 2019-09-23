(function($){
  LinkFieldAdmin = Garnish.Base.extend({
    init: function(id) {
      var items = [];
      var $field = $('#' + id);

      $field.find('.linkFieldAdmin--tabsRow').each(function(index, rowElement) {
        var $row = $(rowElement);

        if ($row.hasClass('all')) {
          $row.find('.lightswitch').on('change', function() {
            $.each(items, function(index, item) {
              item.$lightswitch.toggleClass('hidden', this.on);
            });
          });
        } else {
          var name = $row.attr('data-name');
          var that = {
            $content: $field.find('.linkFieldAdmin--bodyContent[data-name=\'' + name + '\']'),
            $lightswitch: $row.find('.linkFieldAdmin--tabsLightswitch'),
            $row: $row,
          };

          items.push(that);

          $row.find('.linkFieldAdmin--tabsTab').on('click', function(event) {
            event.preventDefault();
            $.each(items, function(index, item) {
              item.$content.toggleClass('selected', item === that);
              item.$row.toggleClass('selected', item === that);
            });
          });
        }
      });
    },
  });
})(jQuery);
