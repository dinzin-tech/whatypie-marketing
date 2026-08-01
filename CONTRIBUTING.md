# Project Development Rules & Guidelines

This document defines the strict development standards, architectural rules, and coding practices for **all human developers and AI agents** working on this repository.

---

## 🚫 1. Non-Negotiable Rule

1. **DO NOT MODIFY CODE IN VENDOR DIRECTORIES**
   * Do **NOT** edit, reformat, override, or delete any code or files inside `vendor/` or `vendors/`.
   * All third-party dependencies managed by Composer, NPM, or manually placed in vendor folders must remain strictly untouched.
   * If you need to modify or extend third-party behavior, use inheritance, wrapper classes, custom middleware, hooks/events, or update the dependency configuration.

2. **ALWAYS USE THE SIMPLE MVC FRAMEWORK WHEREVER APPLICABLE**
   - Always build application components using the [`dinzin-tech/simple-mvc`](https://github.com/dinzin-tech/simple-mvc) framework dependency specified in `composer.json`.
   - Structure features following the framework's conventions for Models, Views, Controllers, Routes, and Commands (`app/`, `commands/`, `public/`). Avoid monolithic script files or custom routing logic outside the `simple-mvc` framework.

---

## 🏗️ 2. Architecture & Code Quality Standards

* **Modular Code Structure:**
  * Break code into self-contained, reusable modules, services, or components with well-defined boundaries.
  * Maintain **High Cohesion** (group related functions together) and **Low Coupling** (minimize direct dependencies between unrelated modules).
* **DRY (Don't Repeat Yourself):** Audit the codebase before writing custom utilities. Check existing helpers, traits, and services.
* **SOLID Principles:**
  * **Single Responsibility:** Classes, modules, and functions must focus on one clearly defined responsibility.
  * **Open/Closed:** Prefer extending functionality via abstractions/interfaces rather than altering existing core code.
  * **Dependency Inversion:** Depend on abstractions rather than hardcoded implementations.
* **Type Safety & Defensive Programming:**
  * Validate parameters and handle `null`/`undefined` states explicitly.
  * Prevent unhandled exceptions (`NullPointer`, `TypeError`, `Undefined index`).
* **Preserve Existing API Contracts:**
  * Never alter function signatures or API endpoints without refactoring all dependent calls across the codebase.

---

## 🔒 3. Security & Safety Best Practices

* **No Hardcoded Credentials:** Secrets, tokens, passwords, and environment-specific configs MUST be loaded from `.env`. Never commit API keys or sensitive configurations.
* **Input Validation & Sanitization:** Validate and sanitize all user input before processing or querying databases to prevent SQL injection, XSS, and command injection.
* **Accidental Data Loss Prevention:** Destructive operations (e.g., dropping tables, truncating databases, deleting critical storage assets) require verification and explicit confirmation.

---

## 🧪 4. Testing, Logging & Error Handling

* **No Silent Error Swallowing:** Do not wrap failing code in empty `try/catch` blocks or return fake fallback values to hide bugs.
* **Traceable Exception Handling:** Catch specific exceptions, preserve original stack traces, and log helpful context.
* **Mandatory Pre-Commit Verification:** Run all local tests and linting tools before submitting PRs or finalizing changes:
  * Run unit/integration tests (e.g., `vendor/bin/phpunit`).
  * Verify build commands if applicable (`npm run dev` / `npm run build`).

---

## 🎨 5. Formatting & Maintainability

* **Consistent Style:** Follow established language-specific PSR standards (for PHP) and formatting standards used throughout the project.
* **Documentation & Comments:** Preserve all pre-existing docstrings and comments. Add comments only for complex, non-obvious logic. Keep variable and function names self-descriptive.

---

## 🤖 6. AI Agent Guidelines

* Agents MUST read `.agents/AGENTS.md` and `CONTRIBUTING.md` prior to making system-wide changes.
* Agents must justify code modifications using actual log outputs, stack traces, or explicit requirements—never assumptions.
