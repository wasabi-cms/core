define(function(require) {
  var $ = require('jquery');
  var BaseView = require('common/BaseView');
  var WS = require('wasabi');
  require('jquery.tSortable');

  return BaseView.extend({

    events: {
      'tSortable-change': 'onSort'
    },

    initialize: function(options) {
      this.$form = this.$el.closest('form');
      this.$el.tSortable({
        handle: 'a.action-sort',
        placeholder: 'sortable-placeholder',
        opacity: 0.8
      });
    },

    onSort: function(event) {
      var $table = $(event.target);
      $table.find('tbody > tr').each(function(index) {
        $(this).find('input.position').first().val(index);
      });
      $.ajax({
        url: this.$form.attr('action'),
        data: this.$form.serialize(),
        type: 'post',
        success: function(data) {
          WS.eventBus.trigger('wasabi-core--languages-sort', event, data);
        }
      });
    }
  });
});
