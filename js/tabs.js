(function(Drupal) {
Drupal.behaviors.translation_tabs = {
  attach: function(context, settings) {
    if (context.nodeName != '#document') {
      return;
    }

    var tabs = document.querySelectorAll('details.translation-tabs .item-list');
    for (item_list of tabs) {
      new translationTabs(item_list);
    }
  }
}

function translationTabs(item_list) {
  this.item_list = item_list;
  this.wrapper = item_list.parentElement;
  this.active = this.wrapper.querySelector(':scope > .form-wrapper.active');

  var self = this;
  this.item_list.addEventListener('click', function (event) {
    if (event.target.nodeName != 'LI') {
      return;
    }
    self.handler(event.target);
  });
}

translationTabs.prototype.handler = function(tab) {
  this.item_list.querySelector('li.active').classList.remove('active');
  tab.classList.add('active');

  if (this.active) {
    this.active.classList.remove('active');
  }
  this.active = this.wrapper.querySelector(':scope > .field-language-' + tab.dataset.code);
  if (this.active) {
    this.active.classList.add('active');
  }
};

})(Drupal);

