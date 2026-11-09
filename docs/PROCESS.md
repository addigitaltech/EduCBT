# Working process

- Work proceeds in numbered phases; each phase is pushed on its own branch (`phase-N-name`) as a pull request and never merged by Manus.
- Phase code is pushed without running tests.
- A full test run happens after every third phase (first checkpoint: after Phases 1 to 3), and the results are reported.
- Until tests have run, all code is described as untested.
- Never commit secrets, real student data or production credentials.

Note: the GitHub Actions workflow in `.github/workflows/ci.yml` runs automatically on every push. Its results are informational until the phase checkpoint.
