# Kinderzuschlag (Familienkasse)

Form module for the e-formulare.de PDF filler (`kalakotra/pdf-editor`).
Fills the **Antrag auf Kinderzuschlag** of the Familienkasse (KiZ 1) with all annexes the answers require and
merges them into one PDF, or the **Kurzantrag bei unveränderten Verhältnissen** (KiZ 1-K) on its own.
The applicant's signature is stamped on every form the applicant signs.

Requires the pdf-editor app base classes (`app\forms\basemodel\*`, `app\models\FormSubmission`) and pdftk
(`replacement_font` support: pdftk ≥ 3.0).

## Installation

pdf-editor `composer.json`:

```json
"repositories": [
    { "type": "git", "url": "https://github.com/kalakotra/silverstripe-ef-kinderzuschlag" }
],
"require": {
    "kalakotra/silverstripe-ef-kinderzuschlag": "^1.0"
}
```

```bash
composer update kalakotra/silverstripe-ef-kinderzuschlag
vendor/bin/sake db:build --flush
```

`db:build` is mandatory: `DBClassName` is an enum, without it the steps load as plain `MultiFormStep`.

## CMS setup (PDFMultiFormPage)

`Form Class Name` = **Kinderzuschlag**. Upload the original, fillable Familienkasse PDFs:

| Slot | Document | Used | Kept pages |
|---|---|---|---|
| `PDFFile` | KiZ 1 – Antrag auf Kinderzuschlag | full application | 2–end (Vorblatt dropped) |
| `PDFFile2` | KiZ 1-AnA – Anlage Antragsteller/Partner | full application | all |
| `PDFFile3` | KiZ 1-AnK – Anlage Kind | once per child (≤ 6) | all |
| `PDFFile4` | KiZ 5c – Unterhalt/Unterhaltsvorschuss | per child with maintenance "less" or "none" | all |
| `PDFFile5` | KiZ 5 – Verdienstbescheinigung | per earner who attaches the certificate (prefilled employee part) | all |
| `PDFFile6` | KiZ 5a + Beiblatt – Einkommen aus selbständiger Tätigkeit | applicant / partner self-employed | all |
| `PDFFile7` | KiZ 6 – erhebliches Vermögen | "erhebliches Vermögen" = yes | all |
| `PDFFile8` | KiZ 7 – Mehrbedarf kostenaufwändige Ernährung | per person with that Mehrbedarf | all (doctor part stays empty) |
| `PDFFile9` | KiZ 1-K – Kurzantrag | short application only | 2–end (Hinweise dropped) |

Merge order: KiZ 1, AnA, per child (AnK, 5c), 5a (applicant, partner), 5 (applicant, partner, children), 6,
7 (applicant, partner, children). KiZ 1 Hinweise is not part of the package.

`SignaturePositionX/Y` – leave empty (module defaults); if set, they move the stamp of KiZ 1 and KiZ 1-K.

## Flow

| Step | Content | Active when |
|---|---|---|
| Form_Step1 | full / short application, short-application checks (all must be "no"), Kindergeldnummer(n) | always |
| Form_Applicant | name, birth data, address | always |
| Form_Family | gender, nationality, marital status, partner (KiZ 1 Punkt 1–2, AnA partner part) | full |
| Form_Bank | IBAN (mod-97 check), BIC, number of children (1–6) | full |
| Form_Child1…6 / Form_ChildIncome1…6 | AnK per child; income, maintenance (KiZ 5c), expenses | child N ≤ ChildCount |
| Form_Household | KiZ 1 Punkt 5–6 (absent children, other persons) | full |
| Form_Living | AnA: own circumstances, housing, assets yes/no, Mehrbedarf | full |
| Form_IncomeApplicant / Form_IncomePartner | AnA income + expenses | full / partner |
| Form_SelfEmployedApplicant/Partner + Form_LedgerApplicant/Partner | KiZ 5a + Beiblatt (6 months × 24 rows) | self-employed |
| Form_Assets | KiZ 6 | assets = yes |
| Form_Preview | e-mail, signature, AGB | always |

Short application: Step1 → Applicant → Preview, only KiZ 1-K.

## How it works

- **Models** – `Submission` (KiZ 1, AnA, 1-K, 6, 7, 5 tokens), `SubmissionChild` (AnK + 5c, one row per child),
  `SubmissionSelfEmployed` (5a + Beiblatt; sums, totals and the six months before the application month are
  computed). `Form::finish()` splits the step data (`Child{N}…`, `SEApplicant…`, `SEPartner…`) in one transaction.
- **Repeated entities** – one step class per instance on top of a trait (`ChildStepTrait`, `ChildIncomeStepTrait`,
  `IncomeStepTrait`, `SelfEmployedStepTrait`, `LedgerStepTrait`) with `FIELD_PREFIX`; `__TRAIT__` lang keys.
- **Flow table** – `StepTrait::FLOW` + `isFlowStepActive()` decide next/previous step from saved data.
- **`$field_conditions`** – single source for field dependencies (mirrors display-logic): step cleanup,
  conditional required fields and `onBeforeWrite()` cleanup, so hidden values never reach a PDF. Entity rules are
  prefixed per instance (`FieldConditions::prefix()`).
- **Mappings** – one `*_field_mapping` per document (`'PDF field' => 'DbField'` / `'Method:Field:Arg'`), checkboxes
  via `$state_map` (all on-state `1`). Note the forms' own naming: in AnA `Alg-1` = Grundsicherung, `Alg-2` =
  Arbeitslosengeld, in AnK the other way round; several KiZ 5a fields are misnamed (`Betriebssitz[0]` = Rechtsform,
  `qm-insgesamt[0..4]` = Zuschuss-Daten / m²) – the mapping comments name the real meaning.
- **`PdfPackage`** – fills + flattens each part, keeps its page range, merges with pdftk, then FPDI draws white
  masks over the flattened push buttons, the IBAN/BIC comb boxes (one character per box) and the signatures.
  Page counts come from pdftk (the free FPDI parser cannot read the compressed originals).
- **Replacement font** – `fonts/LiberationSans-Regular.ttf` (SIL OFL 1.1) as pdftk `replacement_font`;
  without support the values are transliterated to ASCII (warning in the log). Config `Submission.replacement_font`.

## Configuration

Positions are in mm from the top-left corner of the source page of each document; override single values:

```yaml
Kalakotra\EFormKinderzuschlag\Model\Submission:
  documents:
    antrag:
      stamps:
        main: { page: 3, x: 110.0, y: 203.0, width: 32.0 }
```

Not stamped on purpose: partner / second applicant, employer (KiZ 5), doctor (KiZ 7), Familienkasse fields.
