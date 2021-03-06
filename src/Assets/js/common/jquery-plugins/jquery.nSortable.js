(function($, doc, win) {
  "use strict";

  var NestedSortable = function(el, options) {
    this.$el = $(el);
    this.settings = $.extend({}, $.fn.nSortable.defaults, options);

    this.isDragging = false;
    this.$li = null;
    this.$clone = null;
    this.$placeholder = null;
    this.$items = [];
    this.$lis = [];

    this.isLastItem = false;
    this.hasPrevItem = false;
    this.placeholderIndex = 0;
    this.$placeholderUl = null;
    this.$placeholderParentLi = null;
    this.$placeholderUlChildren = null;

    this.startPosition = {};
    this.startDepth = null;
    this.targetDepth = null;
    this.liOffset = {};
    this.delta = {
      x: 0,
      y: 0
    };

    this.isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;

    this.childLevels = 0;

    this.placeholderWidth = null;
    this.placeholderHeight = null;
    this.placeholderDepth = null;
    this.placeholderOffset = {top: 0, left: 0};

    this.$scrollParent = null;

    this._primaryEvents = [];
    this._secondaryEvents = [];

    this._results = [];

    this._buildEvents();
    $.attachEvents(this._primaryEvents);
  };

  NestedSortable.prototype = {

    _buildEvents: function() {
      this._primaryEvents = [
        [this.$el, 'li ' + this.settings.containerElement + ' ' + this.settings.handle, [
          ['mousedown', $.proxy(this._onMouseDown, this)],
          ['mouseup', $.proxy(this._onMouseUp, this)]
        ]]
      ];

      this._secondaryEvents = [
        [$(doc), {
          mousemove: $.proxy(this._onMouseMove, this)
        }]
      ];
    },

    _onMouseDown: function(event) {
      event.preventDefault();
      this._initStart(event);
      $.attachEvents(this._secondaryEvents);
    },

    _onMouseUp: function(event) {
      var that = this;
      $.detachEvents(this._secondaryEvents);
      if (this.isDragging) {
        if (this.settings.animateTarget) {
          this.$clone.insertBefore(this.$placeholder);
          this.$clone.animate({
            top: this.placeholderOffset.top,
            left: this.placeholderOffset.left
          }, parseInt(this.settings.animationLength), _stop);
        } else {
          _stop();
        }
      }

      function _stop() {
        that.$clone.remove();
        that.$clone = null;
        that.$li.insertBefore(that.$placeholder);
        that.$placeholder.remove();
        that.$placeholder = null;
        that.$li.show();
        that.$li = null;
        that.$scrollParent = null;
        that.childLevels = 0;
        that.isDragging = false;
        that._removeEmptyUls();
        var $emptyULs = that.$el.find('ul').not(':has(li)');
        $emptyULs.parent().addClass(that.settings.noChildClass);
        $emptyULs.remove();
        that._trigger('nSortable-change', event);
      }
    },

    _onMouseMove: function(event) {
      var that = this;

      if (!this.isDragging) {
        this._initClone();
        this._initPlaceholder();
        this._updatePlaceholderVars();
        this.$li.hide();
        this.$scrollParent = this.$clone.scrollParent();
        this.$items = this.$el.find('li').filter(function() {
          var $this = $(this);
          return (
            ($this[0] !== that.$li[0]) &&
            ($this.css('display') !== 'none') &&
            ($this.css('position') !== 'absolute') &&
            !$this.parents('li').filter(function() {
              return ($(this).css('position') === 'absolute') || $(this).is(that.$li);
            }).length &&
            !$this.hasClass(that.settings.placeholder)
          );
        }).find(this.settings.containerElement);
        this.isDragging = true;
      }
      this._updateClonePosition(event);
      this._updateDelta(event);

      if (this.settings.scroll === true) {
        this._scroll(event);
        this._updateDelta(event);
      }

      this._updateTargetDepth();

      var $prevItem;
      var $prevItemUl;

      if (this._yIntersectsPlaceholder(event)) {
        // move placeholder to parent list if it is the last item of it's current list
        if (this.isLastItem && (this.targetDepth < this.placeholderDepth)) {
          this.$placeholder.insertAfter(this.$placeholderParentLi);
          if (!this.$placeholderUlChildren.length) {
            this.$placeholderParentLi.addClass(this.settings.noChildClass);
          }
          this._updatePlaceholderVars();
          return;
        }

        // make the item a child of its prev item
        if (this.hasPrevItem && (this.targetDepth > this.placeholderDepth)) {
          $prevItem = $(this.$lis.get(this.placeholderIndex - 1));
          if ($prevItem[0] === this.$li[0]) {
            $prevItem = $(this.$lis.get(this.placeholderIndex - 2));
          }
          $prevItemUl = $prevItem.find('> ul');
          if ($prevItem.length && !$prevItemUl.parent().hasClass('closed')) {
            if (!this.settings.mayHaveChildren($prevItem)) {
              return;
            }
            if (!$prevItemUl.length) {
              $prevItemUl = $('<ul></ul>');
              $prevItem.append($prevItemUl);
            }
            $prevItemUl.append(this.$placeholder).parent().removeClass(this.settings.noChildClass);
            this._updatePlaceholderVars();
          }
          return;
        }
      }

      var $intersectedItem = null;
      var direction = null;

      this.$items.each(function() {
        var min = $(this).position().top;
        var max = min + $(this).outerHeight();
        var middle = parseInt((min + max) / 2);
        var y = event.pageY;
        if (y >= min && y < middle) {
          $intersectedItem = $(this).parent();
          direction = 'up';
          return;
        }
        if (y > middle && y <= max ) {
          $intersectedItem = $(this).parent();
          direction = 'down';
          //noinspection ALL
          return;
        }
      });

      if ($intersectedItem === null) {
        return;
      }

      if (direction === 'up') {
        if ($intersectedItem.prev().hasClass(this.settings.placeholder)) {
          return;
        }
        this.$placeholder.insertBefore($intersectedItem);
        if (!this.$placeholderUlChildren.length) {
          this.$placeholderParentLi.addClass(this.settings.noChildClass);
        }
        this._updatePlaceholderVars();
        return;
      }
      if (direction === 'down') {
        if ($intersectedItem.next().css('display') === 'none' && $intersectedItem.next().next().hasClass(this.settings.placeholder)) {
          return;
        }
        this.$placeholder.insertAfter($intersectedItem);
        if (!this.$placeholderUlChildren.length) {
          this.$placeholderParentLi.addClass(this.settings.noChildClass);
        }
        this._updatePlaceholderVars();
      }
    },

    _initStart: function(event) {
      this.$li = $(event.target).closest('li');
      this.childLevels = this._getChildLevels(this.$li);

      this.startPosition = {
        x: event.pageX,
        y: event.pageY
      };

      this.startDepth = this.$li.parents('ul').length - 1;

      var liOffset = this.$li.offset();
      this.liOffset = {
        x: this.startPosition.x - liOffset.left,
        y: this.startPosition.y - liOffset.top
      };
    },

    _initClone: function() {
      this.$clone = this.$li.clone();
      this.$clone.css({
        display: 'list-item',
        width: this.$li.outerWidth(),
        height: this.$li.outerHeight(),
        position: 'absolute',
        zIndex: 10000,
        opacity: this.settings.opacity
      });
      this.$li.parent().append(this.$clone);
    },

    _initPlaceholder: function() {
      this.placeholderHeight = this.$li.innerHeight();
      this.placeholderWidth = this.$li.outerWidth();
      this.$placeholder = $('<li></li>');
      this.$placeholder
        .addClass(this.settings.placeholder)
        .css({
          height: this.placeholderHeight
        });
      this.$li.after(this.$placeholder);
    },

    _updateClonePosition: function(event) {
      this.$clone[0].style.left = event.pageX - this.liOffset.x + 'px';
      this.$clone[0].style.top = event.pageY - this.liOffset.y + 'px';
    },

    _updateDelta: function(event) {
      this.delta.x = -(this.startPosition.x - event.pageX);
      this.delta.y = this.startPosition.y - event.pageY;
    },

    _updateTargetDepth: function() {
      var diff = parseInt(this.delta.x / this.settings.tabWidth);
      var targetDepth = this.startDepth + diff;
      targetDepth = Math.max(targetDepth, 0);
      targetDepth = Math.min(targetDepth, this.settings.maxDepth);
      targetDepth = Math.min(targetDepth, this.settings.maxDepth - this.childLevels);

      this.targetDepth = targetDepth;
    },

    _getChildLevels: function($parent, depth) {
      var result = 0;
      depth = depth || 0;

      $parent.children('ul').children('li').each($.proxy(function (index, child) {
        result = Math.max(this._getChildLevels($(child), depth + 1), result);
      }, this));

      return depth ? result + 1 : result;
    },

    _scroll: function(event) {
      var sParent = this.$scrollParent[0], overflowOffset = this.$scrollParent.offset();
      if (sParent != doc && sParent.tagName != 'HTML') {
        if ((overflowOffset.top + sParent.offsetHeight - event.pageY) < this.settings.scrollSensitivity) {
          sParent.scrollTop = sParent.scrollTop + this.settings.scrollSpeed;
        } else if (event.pageY - overflowOffset.top < this.settings.scrollSensitivity) {
          sParent.scrollTop = sParent.scrollTop - this.settings.scrollSpeed;
        }
        if ((overflowOffset.left + sParent.offsetWidth - event.pageX) < this.settings.scrollSensitivity) {
          sParent.scrollLeft = sParent.scrollLeft + this.settings.scrollSpeed;
        } else if(event.pageX - overflowOffset.left < this.settings.scrollSensitivity) {
          sParent.scrollLeft = sParent.scrollLeft - this.settings.scrollSpeed;
        }
      } else {
        var $doc = $(doc), $win = $(win);
        if (event.pageY - $doc.scrollTop() < this.settings.scrollSensitivity) {
          $doc.scrollTop($doc.scrollTop() - this.settings.scrollSpeed);
        } else if ($win.height() - (event.pageY - $doc.scrollTop()) < this.settings.scrollSensitivity) {
          $doc.scrollTop($doc.scrollTop() + this.settings.scrollSpeed);
        }
        if (event.pageX - $doc.scrollLeft() < this.settings.scrollSensitivity) {
          $doc.scrollLeft($doc.scrollLeft() - this.settings.scrollSpeed);
        } else if ($win.width() - (event.pageX - $doc.scrollLeft()) < this.settings.scrollSensitivity) {
          $doc.scrollLeft($doc.scrollLeft() + this.settings.scrollSpeed);
        }
      }
    },

    _yIntersectsPlaceholder: function(event) {
      var min = this.placeholderOffset.top;
      var max = min + this.placeholderHeight;
      return (event.pageY >= min && event.pageY <= max);
    },

    _xIntersectsPlaceholder: function(event) {
      var min = this.placeholderOffset.left;
      var max = min + this.placeholderWidth;
      return (event.pageX >= min && event.pageX <= max);
    },

    _updatePlaceholderVars: function() {
      var that = this;
      this.placeholderDepth = that.$placeholder.parentsUntil(that.$el, 'ul').length;
      this.$lis = that.$placeholder.parent().find('> li').filter(function() {
        return ($(this).css('display') !== 'none' && $(this).css('position') !== 'absolute');
      });
      setTimeout(function() {
        that.placeholderOffset = that.$placeholder.offset();
      }, 0);
      this.placeholderIndex = this.$lis.index(this.$placeholder);
      this.isLastItem = (this.placeholderIndex === this.$lis.length - 1);
      this.hasPrevItem = (this.placeholderIndex > 0);
      this.$placeholderUl = this.$placeholder.parent();
      this.$placeholderParentLi = this.$placeholderUl.parent();
      this.$placeholderUlChildren = this.$placeholderUl.find('> li').filter(function() {
        return ($(this).css('display') !== 'none' && !$(this).hasClass(that.settings.placeholder) && $(this).css('position') !== 'absolute');
      });
      that._removeEmptyUls();
    },

    _removeEmptyUls: function() {
      var $emptyULs = this.$el.find('ul').not(':has(li)');
      $emptyULs.parent().addClass(this.settings.noChildClass);
      $emptyULs.remove();
    },

    _trigger: function(eventType, eventOrigin) {
      this.$el.trigger(eventType, {
        event: eventOrigin,
        toArray: $.proxy(this.toArray, this),
        serialize: $.proxy(this.serialize, this)
      });
    },

    _recursiveArray: function($item, depth, left) {
      var that = this,
        right = left + 1,
        id,
        parentId,
        $children = $item.find('> ul > li'),
        tmpItem = {};

      if ($children.length) {
        depth++;
        $children.each(function () {
          right = that._recursiveArray($(this), depth, right);
        });
        depth--;
      }

      id = $item.attr(that.settings.dataAttribute);

      if (depth === 1) {
        parentId = this.settings.serializeParentIdNullable ? null : 0;
      } else {
        parentId = $item.parent().parent().attr(that.settings.dataAttribute);
      }

      if (id) {
        tmpItem = {
          "id": id,
          "parent_id": parentId
        };
        tmpItem[this.settings.leftKey] = left;
        tmpItem[this.settings.rightKey] = right;
        tmpItem[this.settings.depthKey] = depth;
        this._results.push(tmpItem);
      }

      left = right + 1;
      return left;
    },

    serialize: function() {
      var results = this.toArray(), str = [], _i = 0, _len = results.length;

      for (; _i < _len; _i++) {
        var item = results[_i];
        var baseStr = this.settings.serializeKey;
        if (this.settings.serializeWrapKeys) {
          baseStr += '[' + _i + ']';
        } else {
          baseStr += _i;
        }
        for (var attr in item) {
          if (item.hasOwnProperty(attr)) {
            str.push(baseStr + '[' + attr + ']=' + item[attr]);
          }
        }
      }

      return str.join('&');
    },

    toArray: function() {
      var left = 1, that = this;

      this._results = [];

      this.$el.children('li').each(function () {
        left = that._recursiveArray($(this), 1, left);
      });

      this._results = this._results.sort(function(a,b){ return (a.left - b.left); });

      return this._results;
    }
  };

  $.fn.nSortable = function(options) {
    if (!options || typeof options === 'object') {
      return this.each(function() {
        if (!$(this).data('nSortable')) {
          $(this).data('nSortable', new NestedSortable(this, options));
        }
      });
    } else if (typeof options === 'string' && options.charAt(0) !== '_') {
      var nSortable = this.data('nSortable');
      if (!nSortable) {
        throw new Error('nSortable is not initialized on this DOM element.');
      }
      if (nSortable && nSortable[options]) {
        return nSortable[options].apply(nSortable, Array.prototype.slice.apply(arguments, [1]));
      }
    }
    throw new Error('"' + options + '" is no valid api method.');
  };

  $.fn.nSortable.defaults = {
    handle: 'div',
    tabWidth: 20,
    containerElement: '> div',
    opacity: 0.6,
    placeholder: 'placeholder',
    noChildClass: 'no-children',
    dataAttribute: 'data-node-id',
    maxDepth: Infinity,
    animateTarget: true,
    animationLength: 300,
    scroll: true,
    scrollSensitivity: 20,
    scrollSpeed: 20,
    serializeKey: 'nodes',
    serializeWrapKeys: true,
    serializeParentIdNullable: false,
    leftKey: 'left',
    rightKey: 'right',
    depthKey: 'depth',
    mayHaveChildren: function() {
      return true;
    }
  };

})(jQuery, document, window);
