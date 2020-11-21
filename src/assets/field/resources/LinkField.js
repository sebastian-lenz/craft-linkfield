(function($) {
  function createSiteHelper(index, inputElement) {
    if (inputElement.id.substr(-13) !== '-linkedSiteId') {
      return;
    }

    var elementSelect = $('.elementselect', inputElement.parentElement).data('elementSelect');
    if (!elementSelect) {
      return;
    }

    elementSelect.on('selectElements', function(event) {
      if (event.elements && event.elements.length) {
        inputElement.value = event.elements[0].siteId;
      }
    });
  }

	LinkField = Garnish.Base.extend({
		$field: null,
		$options: null,
		$optionsHolder: null,
		$settingsHolder: null,
		$typeSelect: null,
		type: null,

		init: function(id) {
			this.$field = $('#' + id);
			this.$typeSelect = this.$field.find('.linkfield--type select');
			this.type = this.$typeSelect.val();

			this.$optionsHolder = this.$field.find('.linkfield--typeOptions');
			this.$options = this.$optionsHolder.find('.linkfield--typeOption');
			this.$settingsHolder = this.$field.find('.linkfield--settings,.linkfield--target');

      this.$field.find('input[type=hidden]').each(createSiteHelper);

			this.addListener(this.$typeSelect, 'change', 'onChangeType');
		},

		onChangeType: function(e) {
			this.type = this.$typeSelect.val();
      this.$settingsHolder.toggleClass('hidden', this.type === '' || this.type === 'empty');
			this.$options.addClass('hidden');
			this.$options.filter('.' + this.type).removeClass('hidden');
		}
	});
})(jQuery);
