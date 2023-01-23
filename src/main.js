import './style.scss'

function doFormat(x, pattern, mask) {
    let strippedValue = x.replace(/[^0-9]/g, "");
    let chars = strippedValue.split('');
    let count = 0;

    let formatted = '';
    for (let i=0; i<pattern.length; i++) {
        const c = pattern[i];
        if (chars[count]) {
            if (/\*/.test(c)) {
                formatted += chars[count];
                count++;
            } else {
                formatted += c;
            }
        } else if (mask) {
            if (mask.split('')[i])
                formatted += mask.split('')[i];
        }
    }
    return formatted;
}

document.querySelectorAll('[data-mask]').forEach(function(e) {
    function format(elem) {
        const val = doFormat(elem.value, elem.getAttribute('data-format'));
        elem.value = doFormat(elem.value, elem.getAttribute('data-format'), elem.getAttribute('data-mask'));

        if (elem.createTextRange) {
            let range = elem.createTextRange();
            range.move('character', val.length);
            range.select();
        } else if (elem.selectionStart) {
            elem.focus();
            elem.setSelectionRange(val.length, val.length);
        }
    }
    e.addEventListener('keyup', function() {
        format(e);
    });
    e.addEventListener('keydown', function() {
        format(e);
    });
    format(e)
});
