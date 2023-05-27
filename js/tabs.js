(function(Drupal) {

Drupal.behaviors.translation_tabs = {
  attach: function(context, settings) {
    if (context.nodeName != '#document') {
      if ('langPack' in context.parentElement.dataset) {
        this.associate_form(context.parentElement);
      }
      return;
    }

    this.selector_form = document.forms['translations-pack-language-selector'];
    this.packed_form = this.selector_form.parentElement.querySelector('form[data-lang-code]');
    this.forms = {};
    this.forms[this.packed_form.dataset.langCode] =  this.packed_form;
    other_forms = this.packed_form.parentElement.querySelectorAll('form[data-lang-root]');
    for (let form of other_forms) {
      var langcode = form.dataset.langRoot.split('_').pop();
      this.forms[langcode] = form;
    };

    // setup tabs
    var packs = this.packed_form.querySelectorAll('.translation-pack');
    this.tabs_init(packs);

    // handle hidden tabs validation
    this.pack_validate();
    
    // package all translation data to form submission
    this.pack_submission(context, settings);
  },
  
  associate_form: function (wrapper) {
    var code = wrapper.dataset.langPack;
    if (code == 'original') {
      return;
    }
    var form_id = this.forms[code].id;
    var widgets = wrapper.querySelectorAll('[name]');
    for (let w of widgets) {
      w.setAttribute('form', form_id);
    }
  },

  tabs_init: function(packs) {
    var item_list = this.packed_form.querySelector('.translations-tabs');
    this.tabs = new translationTabs(item_list, packs);
    for (pack of packs) {
      for (let next_element of pack.querySelectorAll('[data-lang-pack]')) {
        this.associate_form(next_element);
      }
    }
  },

  pack_validate: function() {
    var handler = {
      context: this,
      handleEvent: function(event) {
        for (let langcode in this.context.forms) {
          if (langcode == this.context.packed_form.dataset.langCode) {
            continue;
          }
          let checkbox_name = 'language_selection['+ langcode +']';
          if (!this.context.selector_form.elements[checkbox_name].checked) {
            continue;
          }
          let next_form = this.context.forms[langcode];
          if (!next_form.checkValidity()) {
            this.context.tabs.showByCode(langcode);
            window.alert(Drupal.t('Please check hidden elements for possible validation errors'));
            next_form.reportValidity();
            event.preventDefault();
            return;
          }
        }
      }
    };
    this.packed_form.addEventListener('submit', handler);
    var original_handler = {
      context: this,
      handleEvent: function(event) {
        let langcode = this.context.packed_form.dataset.langCode;
        let next_form = this.context.packed_form;
        if (!next_form.checkValidity()) {
          this.context.tabs.showByCode(langcode);
          window.alert(Drupal.t('Please check hidden elements for possible validation errors'));
          next_form.reportValidity();
          event.preventDefault();
          return;
        }
      }
    };
    var op_element = this.packed_form.elements['op'];
    if ('length' in op_element) {
      for (let action of op_element) {
        action.addEventListener('click', original_handler);
      }
    }
    else {
      op_element.addEventListener('click', original_handler);
    }
  },

  pack_submission: function() {
    var handler = {
      context: this,
      handleEvent: function(event) {
        var control_names = ['form_id', 'form_build_id', 'form_token'];
        let data = event.formData;
        let form_data = new FormData(this.context.selector_form);
        for (let data_item of form_data.entries()) {
          if (control_names.includes(data_item[0])) {
              data_item[0] += '_language_selector';
          }
          data.append(data_item[0], data_item[1]);
        }
        for (let langcode in this.context.forms) {
          if (langcode == this.context.packed_form.dataset.langCode) {
            continue;
          }
          let checkbox_name = 'language_selection['+ langcode +']';
          if (!this.context.selector_form.elements[checkbox_name].checked) {
            continue;
          }
          let next_form = this.context.forms[langcode];
          let form_data = new FormData(next_form);
          for (let data_item of form_data.entries()) {
            data.append(data_item[0], data_item[1]);
          }
        }
      }
    };
    this.packed_form.addEventListener('formdata', handler);
  }
}

function translationTabs(item_list, packs) {
  this.item_list = item_list;
  this.packs = packs;

  var self = this;
  this.item_list.addEventListener('click', function (event) {
    var tab = null;
    if (event.target.nodeName == 'A') {
      tab = event.target.parentElement;
      self.cancel_language(tab);
      return;
    }

    tab = event.target;

    active_code = self.item_list.querySelector('.language-tab.active').dataset.code;
    var form = Drupal.behaviors.translation_tabs.forms[active_code];
    if (form.checkValidity()) {
      self.show(tab);
      self.update_language(tab);
    }
    else {
      window.alert(Drupal.t('Please check hidden elements for possible validation errors'));
      form.reportValidity();
    }
  });
}

translationTabs.prototype.update_language = function(tab) {
  if (tab.dataset.state == 'off') {
    tab.dataset.state = 'on';
    var cancel = document.createElement('a');
    cancel.textContent = ' [X] ';
    cancel.title = Drupal.t('Cancel');
    tab.appendChild(cancel);
    this.enable(tab.dataset.code);

    var name = 'language_selection['+tab.dataset.code+']';
    var selector_form = Drupal.behaviors.translation_tabs.selector_form;
    selector_form.elements[name].checked = true;
  }
};

translationTabs.prototype.cancel_language = function(tab) {
  tab.dataset.state = 'off';
  var a = tab.querySelector('a');
  if (a) {
    tab.removeChild(a);
  }
  this.disable(tab.dataset.code);

  var name = 'language_selection['+tab.dataset.code+']';
  var selector_form = Drupal.behaviors.translation_tabs.selector_form;
  selector_form.elements[name].checked = false;
};


translationTabs.prototype.show = function(tab) {
  this.item_list.querySelector('.language-tab.active').classList.remove('active');
  tab.classList.add('active');

  this.packs.forEach((pack) => {
    let active = pack.querySelectorAll('.form-wrapper.active[data-lang-pack]');
    if (active) {
      active.forEach((elm) => { elm.classList.remove('active'); });
    }

    active = pack.querySelectorAll('.form-wrapper.field-language-' + tab.dataset.code);
    if (active) {
      active.forEach((elm) => { elm.classList.add('active'); });
    }
  });
};

translationTabs.prototype.showByCode = function(code) {
  for (tab of this.item_list.querySelectorAll('.language-tab')) {
    if (tab.dataset.code == code) {
      this.show(tab);
      break;
    }
  }
}

translationTabs.prototype.disable = function(lang_code) {
  var tab = this.item_list.querySelector(`.language-tab[data-code="${lang_code}"`);

  var first_tab = this.item_list.querySelector('.language-tab:first-child');
  this.show(first_tab);
};

translationTabs.prototype.enable = function(lang_code) {
  var tab = this.item_list.querySelector(`.language-tab[data-code="${lang_code}"`);
};

})(Drupal);
