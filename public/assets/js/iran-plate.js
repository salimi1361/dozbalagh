(function () {
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function parsePlate(rawPlate) {
        const plate = String(rawPlate || '').trim();
        let parts = plate.includes('-')
            ? plate.split('-').map(part => part.trim()).filter(Boolean)
            : [];

        if (parts.length >= 4) {
            return {
                leftDigits: parts[0],
                letter: parts[1],
                rightDigits: parts[2],
                cityCode: parts[3]
            };
        }

        const tokens = plate.match(/(\d+)|([^\d\s]+)/gu) || [];
        return {
            leftDigits: tokens[0] || '',
            letter: tokens[1] || '',
            rightDigits: tokens[2] || plate || '---',
            cityCode: tokens[3] || '--'
        };
    }

    window.renderIranPlate = function (rawPlate, options) {
        const config = options || {};
        const parsed = parsePlate(rawPlate);
        const size = config.size || 'sm';
        const extraClass = config.className ? ' ' + config.className : '';
        const title = escapeHtml(rawPlate || 'بدون پلاک');

        return `
            <span dir="ltr" title="${title}" class="iran-plate iran-plate--${escapeHtml(size)}${extraClass}">
                <span class="iran-plate__blue" aria-hidden="true">
                    <span class="iran-plate__flag"><i></i><i></i><i></i></span>
                    <span class="iran-plate__en"><b>I.R.</b><b>IRAN</b></span>
                </span>
                <span class="iran-plate__main">
                    ${parsed.leftDigits ? `<b>${escapeHtml(parsed.leftDigits)}</b>` : ''}
                    ${parsed.letter ? `<b class="iran-plate__letter">${escapeHtml(parsed.letter)}</b>` : ''}
                    <b>${escapeHtml(parsed.rightDigits)}</b>
                </span>
                <span class="iran-plate__city">
                    <small>ایران</small>
                    <b>${escapeHtml(parsed.cityCode)}</b>
                </span>
                <span class="sr-only">${escapeHtml(rawPlate || '')}</span>
            </span>`;
    };
})();
