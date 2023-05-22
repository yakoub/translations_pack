(function(Drupal) {
Drupal.Ajax.prototype.beforeSubmit = function(formValues, element, options) {
  var trigger = options.data._triggering_element_name;
  var button = element[0].querySelector('[name="'+ trigger +'"]');
  if (!button) {
    console.log('button not found');
    return;
  }
  let current_element = button.parentElement;
  var code = null;
  while (current_element.tagName != 'FORM') {
    if ('langPack' in current_element.dataset) {
      code = current_element.dataset.langPack;
      if (code == 'original') {
        code = null;
      }
      break;
    }
    current_element = current_element.parentElement;
  }
  if (!code) {
    return;
  }

  var id_map = {'form_id': {}, 'form_build_id':{}, 'form_token':{}};
  for (index in formValues) {
    for (id in id_map) {
      if (formValues[index].name == id) {
        id_map[id].index = index;
      }
      else if (formValues[index].name == id + '_' + code) {
        id_map[id].value = formValues[index].value;
      }
    }
    if (formValues[index].name == 'translations_pack_active_id') {
      formValues[index].value = code;
    }
  }
  for (id in id_map) {
    formValues[id_map[id].index].value = id_map[id].value;
  }
  var translation_forms = Drupal.behaviors.translation_tabs.forms;
  if (code in translation_forms) {
    var form_data = new FormData(translation_forms[code]);
    for (let data_item of form_data.entries()) {
      let value_item = {name: data_item[0], value: data_item[1]};
      formValues.push(value_item);
    }
  }
};

Drupal.AjaxCommands.prototype.update_build_id =
  function update_build_id(ajax, response, status) {
    let selector = "input[name^=\"form_build_id\"][value=\"".concat(response.old, "\"]");
    document.querySelectorAll(selector).forEach(function (item) {
      item.value = response.new;
    });
  }
})(Drupal);
