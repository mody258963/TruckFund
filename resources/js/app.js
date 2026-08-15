import './bootstrap';

window.moneyInput = (model, initialValue) => ({
    model,
    display: '',
    normalized: '',

    init() {
        this.normalized = this.clean(initialValue);
        this.display = this.withCommas(this.normalized);
    },

    clean(value) {
        let cleaned = String(value ?? '')
            .replace(/,/g, '')
            .replace(/[^\d.]/g, '');

        const firstDecimal = cleaned.indexOf('.');
        if (firstDecimal !== -1) {
            cleaned = cleaned.slice(0, firstDecimal + 1)
                + cleaned.slice(firstDecimal + 1).replace(/\./g, '').slice(0, 2);
        }

        let [whole = '', decimal] = cleaned.split('.');
        whole = whole.replace(/^0+(?=\d)/, '');

        if (whole === '' && decimal !== undefined) {
            whole = '0';
        }

        return decimal !== undefined ? `${whole}.${decimal}` : whole;
    },

    withCommas(value) {
        if (value === '') {
            return '';
        }

        const [whole, decimal] = value.split('.');
        const formattedWhole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        return decimal !== undefined ? `${formattedWhole}.${decimal}` : formattedWhole;
    },

    formatInput() {
        this.normalized = this.clean(this.display);
        this.display = this.withCommas(this.normalized);
    },

    commit(wire) {
        this.normalized = this.clean(this.display);
        this.display = this.withCommas(this.normalized);
        wire.set(this.model, this.normalized === '' ? null : this.normalized);
    },
});
