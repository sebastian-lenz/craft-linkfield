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
    resizeObserver: null,
    resizeClass: '',
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
      this.resizeObserver = this.createResizeObserver();
		},

    createResizeObserver: function() {
		  var that = this;
      function handler(entries) {
        for (var index = 0; index < entries.length; index++) {
          var entry = entries[index];
          if (entry.contentBoxSize && entry.contentBoxSize[0]) {
            that.onResize(entry.contentBoxSize[0].inlineSize);
          } else if (entry.contentBoxSize) {
            that.onResize(entry.contentBoxSize.inlineSize);
          } else {
            that.onResize(entry.contentRect.width);
          }
        }
      }

      try {
        var resizeObserver = new ResizeObserver(handler);
        resizeObserver.observe(this.$field[0]);
        return resizeObserver;
      } catch (error) {
        console.error(error);
      }

      return null;
    },

		onChangeType: function(e) {
			this.type = this.$typeSelect.val();
      this.$settingsHolder.toggleClass('hidden', this.type === '' || this.type === 'empty');
			this.$options.addClass('hidden');
			this.$options.filter('.' + this.type).removeClass('hidden');
		},

		onResize: function(width) {
		  var resizeClass = '';
		  if (width < 300) {
        resizeClass = 'xs';
      } else if (width < 500) {
        resizeClass = 'sm';
      }

      if (this.resizeClass !== resizeClass) {
        this.$field.removeClass(this.resizeClass);
        this.$field.addClass(resizeClass);
        this.resizeClass = resizeClass;
      }
    }
	});
})(jQuery);
