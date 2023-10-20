(function(Drupal) {
Drupal.behaviors.translation_moderation_tabs = {
  initialized: false,
  attach: function(context, settings) {
    if (context.nodeName != '#document' || this.initialized) {
      return;
    }
    this.initialized = true;
    var selector = 'form div.translation-pack.field-moderation_state';
    var wrapper = document.querySelector(selector);
    if (!wrapper) {
      return;
    }

    var sync_states = wrapper.querySelector('input.sync-states[type="checkbox"]');
    var master_select = wrapper.querySelector('.field--name-moderation-state.active select');
    var other_selects = wrapper.querySelectorAll('.field--name-moderation-state:not(.active) select');
    var master_label_language = '';
    var pack_tabs = Drupal.behaviors.translation_tabs.tabs;

    if (sync_states.checked) {
      wrapper.querySelectorAll('[data-lang-pack]').forEach(function(elm) {
        var code = elm.dataset.langPack;
        elm.classList.remove('field-language-' + code);
      });
      
      var active = wrapper.querySelector('.active[data-lang-pack]');
      active.classList.remove('active');
      active.style.display = 'block';
      var labels = active.querySelectorAll('label');
      for (let label_iter of labels) {
        master_label_language = label_iter.textContent.match(/ \(.+\)$/);
        if (master_label_language) {
          label_iter.textContent = label_iter.textContent.replace(master_label_language[0], '');
        }
      }
    }

    sync_states.addEventListener('change', function(event) {
      wrapper.querySelectorAll('[data-lang-pack]').forEach(function(elm) {
        var code = elm.dataset.langPack;

        if (sync_states.checked) {
          if (code == 'original') {
            elm.classList.remove('active');
            elm.style.display = 'block';

            var labels = elm.querySelectorAll('label');
            for (let label_iter of labels) {
              master_label_language = label_iter.textContent.match(/ \(.+\)$/);
              if (master_label_language) {
                label_iter.textContent = label_iter.textContent.replace(master_label_language[0], '');
              }
            }
          }
          else {
            elm.classList.remove('field-language-' + code);
            elm.classList.remove('active');
          }
        }
        else {
          var active_code = pack_tabs.item_list.querySelector('.active').dataset.code;


          if (code == 'original') {
            elm.style.display = null;
            if (elm.classList.contains('field-language-' + active_code)) {
              elm.classList.add('active');
            }
            if (master_label_language) {
              var labels = elm.querySelectorAll('label');
              for (let label_iter of labels) {
                label_iter.textContent = label_iter.textContent + master_label_language[0];
              }
            }
          }
          else {
            elm.classList.add('field-language-' + code);
            if (code == active_code) {
              elm.classList.add('active');
            }
          }
        }
      });
    });

    master_select.addEventListener('change', function(event) {
      if (!sync_states.checked) {
        return;
      }
      for (other_select of other_selects) {
        other_select.selectedIndex = event.target.selectedIndex;
      }
    });

    wrapper.addEventListener('change', (event) => {
      if (event.target.tagName != 'SELECT') {
        return;
      }
      var new_state = event.target.value;
      var all_selects = wrapper.querySelectorAll('.field--name-moderation-state select');
      var disabled = false;
      for (let select_itr of all_selects) {
        if (select_itr.value != new_state) {
          disabled = true;
        }
      }
      if (disabled) {
        sync_states.disabled = true;
      }
    });

  }
}

})(Drupal);
