# Copilot Instructions for Cese Submit Proposal

## Project Overview
This is a **Joomla 6 component** (`com_cesesubmitproposal`) developed by KAINOTOMO PH LTD. The project runs in a dev container (Debian) with PHP, MariaDB, and Apache. The workspace root is a full Joomla 6 installation at `/var/www/html`.

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

### Language Files
Located at:
- Backend: `administrator/language/en-GB/com_cesesubmitproposal.ini`, `.sys.ini`
- Frontend: `language/en-GB/com_cesesubmitproposal.ini`

## Joomla 6 Component Requirements

### Mandatory for Installation
1. **Site service provider**: `components/com_cesesubmitproposal/services/provider.php` must exist and register MVC factory, dispatcher, and component interface
2. **XML manifest structure**: Must include `<files folder="site">` with `src`, `services`, and `tmpl` folders
3. **Namespace declaration**: `<namespace path="src">KAINOTOMO\Component\Cesesubmitproposal</namespace>` in manifest

### Common Gotchas
- **Whitespace in XML**: Language tags must not have newlines: `<language tag="en-GB">file.ini</language>` (not split across lines)
- **Missing service providers**: Both backend and frontend need `services/provider.php` or install fails with "Can't find XML setup file"
- **Router service**: Frontend needs `src/Service/Router.php` for SEF URL support

## Testing
- Install component from Joomla backend: Extensions → Install → Upload Package File
- Use package at: `build/cesesubmitproposal-pkg/packages/{version}/pkg_cesesubmitproposal_{version}.zip`

## Release Process
1. Update version numbers in `build.xml` (`old_version`, `version`, `old_cdate`, `cdate`)
2. Run `ant build`
3. Commit and push
4. Create GitHub release with generated zip file attached
