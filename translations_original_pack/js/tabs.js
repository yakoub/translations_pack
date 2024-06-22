(function(Drupal) {
Drupal.behaviors.translation_original_tabs = {
  initialized: false,
  attach: function(context, settings) {
    if (context.nodeName != '#document' || this.initialized) {
      return;
    }
    this.initialized = true;
    var selector = 'form div.translation-single.form-wrapper';
    var originals = document.querySelectorAll(selector);
    if (!originals) {
      return;
    }
    originals = Array.from(originals);
    var fieldsets = document
      .querySelectorAll('form details.field-group-tab > .details-wrapper > fieldset');
    for (let fieldset of fieldsets) {
      let pack = fieldset.querySelector('.translation-pack');
      if (pack === null) {
        fieldset.classList.add('translation-single');
        originals.push(fieldset);
      }
    }

    var packed_form = Drupal.behaviors.translation_tabs.packed_form;
    var pack_tabs = packed_form.querySelector('table.translations-tabs tbody tr');

    pack_tabs.addEventListener('click', function (event) {
      var tab = null;
      if (event.target.nodeName == 'A') {
        tab = event.target.parentElement;
        self.cancel_language(tab);
        return;
      }
      else if (event.target.nodeName != 'TD') {
        return;
      }
      tab = event.target;
      if (tab.dataset.code == packed_form.dataset.langCode) {
        originals.forEach((elm) => { elm.classList.remove('hide'); });
      }
      else {
        originals.forEach((elm) => { elm.classList.add('hide'); });
      }
    });
  }
};

})(Drupal);
