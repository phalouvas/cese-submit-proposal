# Copilot Instructions for Cese Submit Proposal

## Project Overview
This is a **Joomla 6 component** (`com_cesesubmitproposal`) developed by KAINOTOMO PH LTD for conference proposal submissions. The project runs in a dev container (Debian) with PHP, MariaDB, and Apache. The workspace root is a full Joomla 6 installation at `/var/www/html`.

### Component Features
- **Multi-step form system** (3 steps): Proposal type selection → Detailed form → Summary/Submit
- **Three proposal types**:
  - Working Group submissions
  - Thematically-focused Panel (Individual or Group)
  - Cross-thematic Session (Individual or Group)
- **Group submissions**: Support up to 4 abstracts, each with up to 4 authors
- **Individual submissions**: Single abstract with up to 4 authors
- **Spam protection**: Honeypot field + time-based validation (configurable 3-86400 seconds)
- **Email notifications**: Admin notification + submitter confirmation (confirmation sent only to first author)
- **Article creation**: Saves submissions as published Joomla articles in "Proposals" category
- **Unique aliases**: Timestamp-based to prevent duplicates

## Architecture

### Component Structure (Joomla 4/5/6 MVC Pattern)
- **Backend (Administrator)**: `administrator/components/com_cesesubmitproposal/`
  - `src/` - Namespaced classes: Controllers, Views, Models, Helpers, Extension bootstrap
  - `services/provider.php` - **Required** DI container service provider
  - `tmpl/` - View templates (e.g., `users/default.php`)
  - `cesesubmitproposal.xml` - Component manifest (lives here during development)
  
- **Frontend (Site)**: `components/com_cesesubmitproposal/`
  - `src/` - Namespaced classes: Controllers, Views, Service classes
  - `services/provider.php` - **Required** DI container for frontend
  - `tmpl/` - Frontend templates (e.g., `main/default.php`, `main/default.xml` for menu items)

### Namespace Convention
All classes use namespace `KAINOTOMO\Component\Cesesubmitproposal` with:
- `\Administrator\` prefix for backend classes
- `\Site\` prefix for frontend classes
- Example: `KAINOTOMO\Component\Cesesubmitproposal\Administrator\View\Users\HtmlView`

### Critical Files
- **Component Extension Bootstrap**: `administrator/components/com_cesesubmitproposal/src/Extension/CesesubmitproposalComponent.php` - extends `MVCComponent`, implements `BootableExtensionInterface`, `RouterServiceInterface`, `FieldsServiceInterface`
- **Service Providers**: Both `administrator/components/.../services/provider.php` and `components/.../services/provider.php` are mandatory for Joomla 4+
- **Manifest**: `administrator/components/com_cesesubmitproposal/cesesubmitproposal.xml` defines installable structure

## Build & Package System

### Apache Ant Build Process
Build packages using: `ant build` (run from `/var/www/html`)

The `build.xml` Ant script:
1. **Version Management**: Updates version in manifests via `fix_version_date` target
   - Change `old_version`, `old_cdate`, `version`, `cdate` properties before building
   - Auto-replaces in `pkg_cesesubmitproposal.xml` and `cesesubmitproposal.xml`

2. **Staging**: Copies files to `build/cesesubmitproposal-pkg/staging/com_cesesubmitproposal/`:
   - `admin/` from `administrator/components/com_cesesubmitproposal/`
   - `site/` from `components/com_cesesubmitproposal/`
   - `language/admin/` and `language/site/` from respective language folders
   - Moves `cesesubmitproposal.xml` to staging root

3. **Output**: Creates installable packages at:
   - `build/cesesubmitproposal-pkg/packages/{version}/com_cesesubmitproposal_{version}.zip` (component)
   - `build/cesesubmitproposal-pkg/packages/{version}/pkg_cesesubmitproposal_{version}.zip` (package installer)

### Package Manifest Structure
The `pkg_cesesubmitproposal.xml` at `administrator/manifests/packages/` defines the package wrapper that references the component zip.

## Development Workflow

### Container Setup
1. Clone repo, run `sudo chown -R www-data:www-data .` or `sudo chown -R phalo:phalo .`
2. Open in dev container (VS Code Remote Containers)
3. Inside container: `apt update && apt install -y git ant`
4. Joomla site accessible at forwarded port 8083

### Making Changes
- **Never build during development** - work directly in source folders
- Files already exist in Joomla folders; installer will overwrite on upgrade
- Only run `ant build` when ready to create release package

### Key Implementation Files
**Frontend (Site):**
- `components/com_cesesubmitproposal/src/Controller/ProposalController.php` - Handles form submission flow
  - `saveStep2()` - Saves step 2 data, handles submission type switching (reload parameter)
  - `submit()` - Final submission, spam check, article creation, emails
- `components/com_cesesubmitproposal/src/Model/ProposalModel.php` - Business logic
  - `validateStep2()` - Validates based on submission type (individual vs group)
  - `createProposalArticle()` - Uses Joomla article model, generates unique alias
  - `generateArticleTitle()` - Format: `[Type] - [FirstAuthorSurname] - [AbstractTitle]`
  - `verifySpamProtection()` - Honeypot + time-based checks
  - `sendAdminNotificationEmail()` - To configured admin email
  - `sendConfirmationEmail()` - To first author only (author1_email or abstract1_author1_email)
- `components/com_cesesubmitproposal/tmpl/main/` - View templates
  - `default_step1.php` - Proposal type selection
  - `default_step2.php` - Loads appropriate sub-template based on proposal type
  - `default_workinggroup.php` - Working group form
  - `default_panel_individual.php` - Panel individual (no panel title/summary section)
  - `default_panel_group.php` - Panel group (includes panel title/summary + 4 abstracts)
  - `default_session_individual.php` - Session individual
  - `default_session_group.php` - Session group (includes session title/summary + 4 abstracts)
  - `default_step3.php` - Summary and submit (with spinner on button)
  - `default_success.php` - Confirmation page

**Backend (Administrator):**
- `administrator/components/com_cesesubmitproposal/config.xml` - Component configuration
  - `enable_notifications` - Toggle email notifications
  - `admin_email` - Recipient for admin notifications
  - `enable_confirmation_email` - Toggle confirmation emails
  - `email_subject_prefix` - Prefix for admin email subjects
  - `min_submission_time` - Spam protection timing (default 3 seconds)

### Language Files
Located at:
- Backend: `administrator/language/en-GB/com_cesesubmitproposal.ini`, `.sys.ini`
- Frontend: `language/en-GB/com_cesesubmitproposal.ini`

### Form Data Structure
**Individual Submissions:** `author[1-4]_name`, `author[1-4]_surname`, `author[1-4]_email`, `author[1-4]_affiliation`, `abstract1_title`, `abstract1_details`

**Group Submissions:** `panel_title`, `panel_summary`, `abstract[1-4]_author[1-4]_name`, `abstract[1-4]_author[1-4]_surname`, etc.

**Session Management:** Data stored in `com_cesesubmitproposal.step1`, `com_cesesubmitproposal.step2`, `com_cesesubmitproposal.success`

## Joomla 6 Component Requirements

### Mandatory for Installation
1. **Site service provider**: `components/com_cesesubmitproposal/services/provider.php` must exist and register MVC factory, dispatcher, and component interface
2. **XML manifest structure**: Must include `<files folder="site">` with `src`, `services`, and `tmpl` folders
3. **Namespace declaration**: `<namespace path="src">KAINOTOMO\Component\Cesesubmitproposal</namespace>` in manifest

### Common Gotchas
- **Whitespace in XML**: Language tags must not have newlines: `<language tag="en-GB">file.ini</language>` (not split across lines)
- **Missing service providers**: Both backend and frontend need `services/provider.php` or install fails with "Can't find XML setup file"
- **Router service**: Frontend needs `src/Service/Router.php` for SEF URL support
- **SEF URLs and task parameter**: Form action should use `Route::_()` for URL but task must be sent as POST hidden field (`<input type="hidden" name="task" value="proposal.saveStep2">`) to work with SEF URLs
- **Submission type switching**: When radio buttons change submission type, add `reload=1` parameter to skip validation and redirect back to step 2
- **Validation differences**: Individual vs Group submissions have different field names - validation must check `submission_type` to validate correct fields
- **Article title generation**: Must check `submission_type` to get author from correct field (`author1_surname` for individual, `abstract1_author1_surname` for group)
- **Email recipients**: Confirmation email only to first author, determined by submission type

## Testing
- Install component from Joomla backend: Extensions → Install → Upload Package File
- Use package at: `build/cesesubmitproposal-pkg/packages/{version}/pkg_cesesubmitproposal_{version}.zip`

## Release Process
1. Update version numbers in `build.xml` (`old_version`, `version`, `old_cdate`, `cdate`)
2. Run `ant build`
3. Commit and push
4. Create GitHub release with generated zip file attached
