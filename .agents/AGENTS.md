# Project Rules for AI Agents

All AI agents working on this workspace MUST adhere strictly to the rules outlined below as well as the rules defined in [CONTRIBUTING.md](../CONTRIBUTING.md).

---

## 🚫 1. Absolute Non-Negotiable Rule

1. **NEVER MODIFY ANYTHING IN VENDOR DIRECTORIES**
   - Path scope: `vendor/`, `vendors/`, `node_modules/` or any third-party library folders.
   - You MUST NOT edit, format, patch, or delete files in these paths.
   - If a fix or extension is needed, implement it in application code (`app/`, `src/`, etc.) by extending or wrapping the vendor class/function.

2. **ALWAYS USE THE SIMPLE MVC FRAMEWORK WHEREVER APPLICABLE**
   - Always build application components using the [`dinzin-tech/simple-mvc`](https://github.com/dinzin-tech/simple-mvc) framework package specified in `composer.json`.
   - Structure features following the framework's conventions for Models, Views, Controllers, Routes, and Commands (`app/`, `commands/`, `public/`). Avoid creating monolithic scripts or bypassing the framework architecture.

---

## 🛡️ 2. Core Operational Rules for AI Agents

* **Modular Coding Practice:** Design changes modularly with high cohesion and low coupling. Avoid creating monolithic functions/classes; break complex logic into isolated, testable modules or service classes.
* **Preserve Code and Comments:** Do NOT remove docstrings, inline comments, or existing helper functions that are unrelated to your assigned task.
* **Empirical Log Verification:** Base all bug diagnoses on actual log evidence (`storage/logs/access.log`, stack traces, terminal test outputs). Never guess root causes or invent error reasons.
* **No Symptom Masking:** Do not wrap failing code in silent `try/catch` blocks or return hardcoded dummy data just to pass a check. Fix the underlying root cause.
* **Pre-Completion Test Runs:** Never declare success on a coding or debugging task without executing the appropriate test suite (e.g., `vendor/bin/phpunit`) or build commands to prove the fix works.
* **Respect Environment Boundaries:** All environment configurations, DB parameters, and secrets MUST be read from `.env` or `.env.example`. Never hardcode secrets in code.
