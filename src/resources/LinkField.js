(function($){
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
			this.$settingsHolder = this.$field.find('.linkfield--settings');

			this.addListener(this.$typeSelect, 'change', 'onChangeType');
		},

		onChangeType: function(e) {
			this.type = this.$typeSelect.val();
      this.$settingsHolder.toggleClass('hidden', this.type === '');
			this.$options.addClass('hidden');
			this.$options.filter('.' + this.type).removeClass('hidden');
		}
	});
})(jQuery);
