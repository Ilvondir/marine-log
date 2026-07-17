# E2E Testing Rules — MarineLog

- Use getByRole, getByLabel, getByText as primary locators.
  Fall back to getByTestId only when accessibility attributes are ambiguous.
- Never use CSS selectors, XPath, or DOM structure for locating elements.
- Each test must be independently runnable — no shared state between tests.
- Never use page.waitForTimeout(). Wait for specific conditions:
  toBeVisible(), waitForURL(), waitForResponse().
- Assert the business outcome, not implementation details.
- Use unique identifiers (e.g., Date.now() suffix) for test data
  to avoid collisions in parallel runs. Clean up in afterEach or within the test.
- Use storageState for authentication — never log in through UI
  in individual tests.
- Test names must reference the risk from context/foundation/test-plan.md
  they protect (e.g., "risk #1 — publish flow").
- One test per file. File name = scenario name in kebab-case.
- Seed test (tests/e2e/seed.spec.ts) is the exemplar — match its style.
- Internal boundaries (auth, routing, DB) stay real.
  Mock only expensive/non-deterministic external APIs at the network layer.
- Every assertion must fail if the risk from test-plan.md materializes.
  Control question: "Would this go red if the failure scenario occurred?"
