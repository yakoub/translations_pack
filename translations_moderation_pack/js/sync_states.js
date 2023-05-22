(function(Drupal) {
Drupal.behaviors.translation_moderation_tabs = {
  attach: function(context, settings) {
    if (context.nodeName != '#document') {
      return;
    }
    var selector = 'form div.translation-pack.field-moderation_state';
    var wrapper = document.querySelector(selector);
    if (!wrapper) {
      return;
    }

    var sync_states = wrapper.querySelector('input.sync-states[type="checkbox"]');
    var master_select = wrapper.querySelector('.field--name-moderation-state.active select');
    var other_selects = wrapper.querySelectorAll('.field--name-moderation-state:not(.active) select');

    if (sync_states.checked) {
      wrapper.querySelectorAll('[data-lang-pack]').forEach(function(elm) {
        var code = elm.dataset.langPack;
        elm.classList.remove('field-language-' + code);
      });
      var active = wrapper.querySelector('.active[data-lang-pack]');
      active.classList.remove('active');
      active.style.display = 'block';
    }

    sync_states.addEventListener('change', function(event) {
      wrapper.querySelectorAll('[data-lang-pack]').forEach(function(elm) {
        var code = elm.dataset.langPack;

        if (sync_states.checked) {
          if (code == 'original') {
            elm.classList.remove('active');
            elm.style.display = 'block';
          }
          else {
            elm.classList.remove('field-language-' + code);
          }
        }
        else {
          if (code == 'original') {
            elm.style.display = null;
            elm.classList.add('active');
          }
          else {
            elm.classList.add('field-language-' + code);
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

  }
}

})(Drupal);
