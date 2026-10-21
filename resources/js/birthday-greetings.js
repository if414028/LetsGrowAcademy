export default (customers, selected, sampleMessage) => ({
    customers,
    selected: selected.map(Number).filter((id) => customers.some((customer) => customer.id === id)),
    saved: [],
    query: '',
    editing: false,
    saving: false,
    previewId: null,
    previewName: 'Nama Customer',
    previewMessage: sampleMessage,
    init() {
        this.saved = [...this.selected];
        const first = this.customers.find((customer) => this.selected.includes(customer.id));
        if (first) this.setPreview(first);
    },
    get visibleCustomers() {
        const term = this.query.trim().toLocaleLowerCase('id');
        return this.customers.filter((customer) => (this.editing || this.selected.includes(customer.id))
            && `${customer.name} ${customer.phone || ''}`.toLocaleLowerCase('id').includes(term));
    },
    initials(name) {
        return name.trim().split(/\s+/).slice(0, 2).map((word) => word[0]).join('').toLocaleUpperCase('id');
    },
    startEditing() {
        this.query = '';
        this.editing = true;
        this.$nextTick(() => this.$refs.search.focus());
    },
    cancelEditing() {
        this.selected = [...this.saved];
        this.editing = false;
        this.query = '';
        this.$nextTick(() => this.$refs.editButton.focus());
    },
    toggle(id) {
        const customer = this.customers.find((item) => item.id === id);
        if (!customer || (!customer.eligible && !this.selected.includes(id))) return;
        this.selected = this.selected.includes(id) ? this.selected.filter((value) => value !== id) : [...this.selected, id];
        this.setPreview(customer);
    },
    selectVisible() {
        this.selected = [...new Set([...this.selected, ...this.visibleCustomers.filter((customer) => customer.eligible).map((customer) => customer.id)])];
    },
    clearVisible() {
        const visible = this.visibleCustomers.map((customer) => customer.id);
        this.selected = this.selected.filter((id) => !visible.includes(id));
    },
    setPreview(customer) {
        this.previewId = customer.id;
        this.previewName = customer.name;
        this.previewMessage = customer.message;
    },
    preview(customer) {
        this.setPreview(customer);
        if (window.matchMedia('(max-width: 1199px)').matches) {
            this.$refs.previewPanel.focus({ preventScroll: true });
            this.$refs.previewPanel.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
        }
    },
});
