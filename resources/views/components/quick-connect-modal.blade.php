{{-- Conectar por IP: cadastra a conexão (nome automático) e abre o Explorer, igual a uma conexão normal --}}
<div class="modal fade lp-modal" id="quickConnectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:15px;font-weight:600">
                    <i class="bi bi-lightning-charge-fill me-2" style="color:var(--lp-blue)"></i>
                    Conectar por IP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickConnectForm">
                @csrf
                <div class="modal-body">
                    <p class="mb-3" style="color:var(--lp-text-muted);font-size:12.5px">
                        Cadastra este servidor (nome gerado automaticamente) e abre o Explorer na hora.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="lp-form-label" for="qcHost">Host / IP</label>
                            <input type="text" id="qcHost" name="host" class="lp-input" placeholder="192.168.1.10" autofocus>
                        </div>
                        <div class="col-md-4">
                            <label class="lp-form-label" for="qcPort">Porta</label>
                            <input type="number" id="qcPort" name="port" class="lp-input" value="22" min="1" max="65535">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="lp-form-label" for="qcUsername">Usuário SSH</label>
                        <input type="text" id="qcUsername" name="username" class="lp-input" placeholder="root">
                    </div>

                    <div class="mt-3">
                        <label class="lp-form-label">Método de Autenticação</label>
                        <div class="lp-auth-toggle" role="group">
                            <label class="lp-auth-toggle-option">
                                <input type="radio" name="auth_type" value="key" checked>
                                <i class="bi bi-key-fill"></i> Chave SSH
                            </label>
                            <label class="lp-auth-toggle-option">
                                <input type="radio" name="auth_type" value="password">
                                <i class="bi bi-shield-lock-fill"></i> Usuário e Senha
                            </label>
                        </div>
                    </div>

                    <div class="mt-3" id="qcAuthFieldKey">
                        <label class="lp-form-label" for="qcSshKey">Chave Privada SSH</label>
                        <input type="file" id="qcSshKey" name="ssh_key" class="lp-file-input" accept=".pem,.ppk,.key,application/octet-stream">
                    </div>

                    <div class="mt-3" id="qcAuthFieldPassword" style="display:none">
                        <label class="lp-form-label" for="qcPassword">Senha SSH</label>
                        <input type="password" id="qcPassword" name="password" class="lp-input" placeholder="Senha do usuário no servidor" autocomplete="new-password">
                    </div>

                    <div id="quickConnectResult" class="lp-test-result mt-3" style="display:none"></div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn-lp-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="qcSubmit" class="btn-lp-primary">
                        <i class="bi bi-folder2-open"></i>
                        Conectar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const modalEl   = document.getElementById('quickConnectModal');
    const form      = document.getElementById('quickConnectForm');
    const submitBtn = document.getElementById('qcSubmit');
    const result    = document.getElementById('quickConnectResult');
    const fieldKey      = document.getElementById('qcAuthFieldKey');
    const fieldPassword = document.getElementById('qcAuthFieldPassword');
    const sshKeyInput    = document.getElementById('qcSshKey');
    const passwordInput  = document.getElementById('qcPassword');

    function syncAuthFields() {
        const authType = form.querySelector('input[name="auth_type"]:checked')?.value ?? 'key';
        const isPassword = authType === 'password';

        fieldKey.style.display      = isPassword ? 'none' : '';
        fieldPassword.style.display = isPassword ? '' : 'none';

        sshKeyInput.disabled   = isPassword;
        passwordInput.disabled = !isPassword;
    }

    form.querySelectorAll('input[name="auth_type"]').forEach(el => {
        el.addEventListener('change', syncAuthFields);
    });
    syncAuthFields();

    modalEl.addEventListener('hidden.bs.modal', () => {
        form.reset();
        syncAuthFields();
        result.style.display = 'none';
        result.innerHTML = '';
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat lp-spin"></i> Conectando…';
        result.style.display = 'none';
        result.className = 'lp-test-result mt-3';
        result.innerHTML = '';

        try {
            const { data } = await axios.post('{{ route('connections.quick-connect') }}', new FormData(form), {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            // Same-tab navigation into the Explorer — identical to clicking
            // "Explorer" on a normal, already-saved connection card.
            window.location.href = data.url;
        } catch (err) {
            const errors = err.response?.data?.errors;
            const msg = errors
                ? Object.values(errors).flat().join(' ')
                : (err.response?.data?.message || 'Falha ao conectar. Verifique os dados informados.');

            result.style.display = 'block';
            result.classList.add('error');
            result.innerHTML = `
                <div style="color:var(--lp-red);font-weight:600">
                    <i class="bi bi-x-circle-fill me-1"></i> ${msg}
                </div>
            `;

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-folder2-open"></i> Conectar';
        }
    });
})();
</script>
@endpush
