<script src="https://unpkg.com/petite-vue" defer init></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    function LiveComponent(className, endpoint, initialState = {}) {
    return {
    ...initialState,
    async call(action, payload = {}) {
    const response = await axios.post(endpoint, {
    action,
    state: this,
    params: payload
});
    Object.assign(this, response.data.state);
    if (response.data.html) {
    // replace inner HTML if root element has an id
    const root = document.getElementById(className) || this.$el;
    if(root) root.innerHTML = response.data.html;
}
}
};
}

    function LivyForm(targetSelector = null, flashSelector = null) {
    return {
    submitting: false,
    async submit(event) {
    event.preventDefault();
    const form = event.target;
    const data = new FormData(form);
    this.submitting = true;

    try {
    const response = await axios.post(form.action, data, {
    headers: { 'X-Livy-Request': '1' }
});

    // Update flash messages
    if (flashSelector && response.data?.flash) {
    document.querySelector(flashSelector).innerHTML = response.data.flash;
}

    // Handle redirect
    if (response.data?.redirect) {
    if (targetSelector) {
    const res = await axios.get(response.data.redirect, {
    headers: { 'X-Livy-Partial': '1' }
});
    document.querySelector(targetSelector).innerHTML = res.data.html ?? res.data;
} else {
    window.location = response.data.redirect;
}
}
} catch (err) {
    console.error(err);
} finally {
    this.submitting = false;
}
}
};
}
</script>
