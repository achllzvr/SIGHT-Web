{{--
  Live password strength checklist.
  Props:
    $passwordId (default: password)
    $confirmId (default: password_confirmation)
    $submitSelector (optional CSS selector for submit button(s) to disable until valid)
    $optional (bool) — when true, empty password is allowed (profile change forms)
--}}
@php
    $passwordId = $passwordId ?? 'password';
    $confirmId = $confirmId ?? 'password_confirmation';
    $submitSelector = $submitSelector ?? null;
    $optional = (bool) ($optional ?? false);
    $uid = 'ps-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $passwordId);
@endphp

<div id="{{ $uid }}" class="password-strength" data-optional="{{ $optional ? '1' : '0' }}" hidden>
    <div class="password-strength__meter">
        <span class="password-strength__meter-label">Password strength: <strong data-meter>Weak</strong></span>
        <span class="password-strength__meter-score" data-score>0/5</span>
    </div>
    <div class="password-strength__bar"><div class="password-strength__bar-fill" data-bar></div></div>
    <ul class="password-strength__list">
        <li data-rule="length"><span class="icon"></span><span class="label">At least 8 characters</span></li>
        <li data-rule="lower"><span class="icon"></span><span class="label">Add a lowercase letter (a–z)</span></li>
        <li data-rule="upper"><span class="icon"></span><span class="label">Add an uppercase letter (A–Z)</span></li>
        <li data-rule="number"><span class="icon"></span><span class="label">Add a number (0–9)</span></li>
        <li data-rule="symbol"><span class="icon"></span><span class="label">Add a symbol (!@#$…)</span></li>
        <li data-rule="match"><span class="icon"></span><span class="label">Passwords must match</span></li>
    </ul>
    <p class="password-strength__success" data-success hidden>Password looks good — all requirements met</p>
</div>

<style>
.password-strength {
    margin-top: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    border: 1px solid #d4d4d4;
    background: #f7fbf4;
    font-size: 0.875rem;
    line-height: 1.35;
}
.password-strength__meter {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.4rem;
    color: #6b6b6b;
}
.password-strength__bar {
    height: 6px;
    border-radius: 999px;
    background: rgba(212, 212, 212, 0.55);
    overflow: hidden;
    margin-bottom: 0.75rem;
}
.password-strength__bar-fill {
    height: 100%;
    width: 0%;
    border-radius: 999px;
    background: #f43f5e;
    transition: width 0.2s ease, background 0.2s ease;
}
.password-strength__list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.password-strength__list li {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
    color: #6b6b6b;
}
.password-strength__list li.is-done {
    color: #62b239;
}
.password-strength__list .icon::before {
    content: '○';
}
.password-strength__list li.is-done .icon::before {
    content: '✓';
}
.password-strength__success {
    margin: 0.5rem 0 0;
    color: #62b239;
    font-weight: 600;
}
.password-strength.is-weak .password-strength__meter-label strong { color: #f43f5e; }
.password-strength.is-mid .password-strength__meter-label strong { color: #ffaf03; }
.password-strength.is-strong .password-strength__meter-label strong { color: #62b239; }
</style>

<script>
(function () {
    const root = document.getElementById(@json($uid));
    if (!root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';

    const passwordEl = document.getElementById(@json($passwordId));
    const confirmEl = document.getElementById(@json($confirmId));
    if (!passwordEl || !confirmEl) return;

    const optional = root.dataset.optional === '1';
    const submitSelector = @json($submitSelector);
    const labels = {
        length: { incomplete: 'At least 8 characters', complete: '8+ characters' },
        lower: { incomplete: 'Add a lowercase letter (a–z)', complete: 'Lowercase letter' },
        upper: { incomplete: 'Add an uppercase letter (A–Z)', complete: 'Uppercase letter' },
        number: { incomplete: 'Add a number (0–9)', complete: 'Number' },
        symbol: { incomplete: 'Add a symbol (!@#$…)', complete: 'Symbol' },
        match: { incomplete: 'Passwords must match', complete: 'Passwords match' },
    };

    function evaluate(password, confirm) {
        return {
            length: password.length >= 8,
            lower: /[a-z]/.test(password),
            upper: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            symbol: /[^A-Za-z0-9]/.test(password),
            match: password.length > 0 && password === confirm,
        };
    }

    function scoreOf(state) {
        return ['length', 'lower', 'upper', 'number', 'symbol'].reduce((n, k) => n + (state[k] ? 1 : 0), 0);
    }

    function meterLabel(score) {
        if (score <= 1) return 'Weak';
        if (score <= 3) return 'Getting stronger';
        if (score === 4) return 'Almost there';
        return 'Strong';
    }

    function setSubmitEnabled(enabled) {
        if (!submitSelector) return;
        document.querySelectorAll(submitSelector).forEach((btn) => {
            btn.disabled = !enabled;
            btn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        });
    }

    function refresh() {
        const password = passwordEl.value || '';
        const confirm = confirmEl.value || '';
        const active = password.length > 0 || confirm.length > 0 || document.activeElement === passwordEl || document.activeElement === confirmEl;

        if (!active && password.length === 0 && confirm.length === 0) {
            root.hidden = true;
            if (optional) setSubmitEnabled(true);
            return;
        }

        root.hidden = false;
        const state = evaluate(password, confirm);
        const score = scoreOf(state);
        const fullyValid = score === 5 && state.match;
        const allowEmptyOptional = optional && password.length === 0 && confirm.length === 0;

        Object.keys(labels).forEach((key) => {
            const li = root.querySelector('[data-rule="' + key + '"]');
            if (!li) return;
            const done = !!state[key];
            li.classList.toggle('is-done', done);
            const labelEl = li.querySelector('.label');
            if (labelEl) labelEl.textContent = done ? labels[key].complete : labels[key].incomplete;
        });

        root.classList.remove('is-weak', 'is-mid', 'is-strong');
        root.classList.add(score <= 1 ? 'is-weak' : (score <= 3 ? 'is-mid' : 'is-strong'));
        root.querySelector('[data-meter]').textContent = meterLabel(score);
        root.querySelector('[data-score]').textContent = score + '/5';
        const bar = root.querySelector('[data-bar]');
        bar.style.width = (score / 5 * 100) + '%';
        bar.style.background = score <= 1 ? '#f43f5e' : (score <= 3 ? '#ffaf03' : '#62b239');

        const success = root.querySelector('[data-success]');
        success.hidden = !fullyValid;

        setSubmitEnabled(allowEmptyOptional || fullyValid);
    }

    ['input', 'change', 'keyup', 'focus'].forEach((evt) => {
        passwordEl.addEventListener(evt, refresh);
        confirmEl.addEventListener(evt, refresh);
    });

    const form = passwordEl.closest('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const password = passwordEl.value || '';
            const confirm = confirmEl.value || '';
            if (optional && password.length === 0 && confirm.length === 0) return;
            const state = evaluate(password, confirm);
            const ok = scoreOf(state) === 5 && state.match;
            if (!ok) {
                e.preventDefault();
                root.hidden = false;
                refresh();
            }
        });
    }

    refresh();
})();
</script>
