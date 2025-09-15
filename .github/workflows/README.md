# GitHub Actions Workflows

## Sync V2 Documentation Workflow

The `sync-v2-docs.yml` workflow automatically syncs documentation from the `sigmie/sigmie` repository (v2 branch) to this repository.

### Trigger Methods

#### 1. **Scheduled (Automatic)**
- Runs daily at 2 AM UTC
- No action required

#### 2. **Manual Trigger**
- Go to Actions tab in GitHub
- Select "Sync V2 Documentation"
- Click "Run workflow"

#### 3. **Repository Dispatch (Webhook)**
Trigger via GitHub API or from another GitHub Action:

```bash
# Using curl
curl -X POST \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: Bearer <YOUR_GITHUB_TOKEN>" \
  -H "X-GitHub-Api-Version: 2022-11-28" \
  https://api.github.com/repos/sigmie/sigmie.com/dispatches \
  -d '{"event_type":"sync-v2-docs"}'
```

```javascript
// Using GitHub API (from another workflow)
await github.rest.repos.createDispatchEvent({
  owner: 'sigmie',
  repo: 'sigmie.com',
  event_type: 'sync-v2-docs',
  client_payload: {
    triggered_by: 'external-source',
    timestamp: new Date().toISOString()
  }
});
```

#### 4. **Workflow Call**
Call this workflow from another workflow in the same repository:

```yaml
jobs:
  sync-docs:
    uses: ./.github/workflows/sync-v2-docs.yml
    secrets:
      token: ${{ secrets.GITHUB_TOKEN }}
```

### Setting Up External Triggers

To trigger from the `sigmie/sigmie` repository when v2 docs are updated:

#### Option 1: Simple Method (No Token Required!)
Since both repos are in the same organization, you can use the built-in `GITHUB_TOKEN`:

1. Copy the example from `example-trigger-from-sigmie-repo.yml.example`
2. Add it to `sigmie/sigmie` repository as `.github/workflows/trigger-docs-sync.yml`
3. That's it! The workflow uses the automatic `GITHUB_TOKEN`

#### Option 2: Using Repository Dispatch (If needed)
Only required if you need to pass custom data or use repository_dispatch specifically:

1. **Create a Personal Access Token (PAT)**:
   - Go to GitHub Settings > Developer settings > Personal access tokens
   - Generate a token with `repo` scope
   - Save the token

2. **Add Token to Source Repository**:
   - In `sigmie/sigmie` repository
   - Go to Settings > Secrets and variables > Actions
   - Add secret: `SIGMIE_COM_TRIGGER_TOKEN`
   - Paste the token value

3. **Uncomment the dispatch method** in the example workflow

### Workflow Permissions

The workflow requires:
- `contents: write` - To commit and push changes
- `GITHUB_TOKEN` - Automatically provided by GitHub Actions

### Files Structure

```
resources/docs/v2/      # Target directory for synced documentation
.github/workflows/
├── sync-v2-docs.yml    # Main sync workflow
├── example-trigger-from-sigmie-repo.yml.example  # Example trigger workflow
└── README.md           # This file
```

### Monitoring

- Check Actions tab for workflow runs
- Each run provides a summary with sync status
- Failed syncs will show in the Actions tab with error details

### Troubleshooting

1. **No changes detected**: Documentation is already up-to-date
2. **Permission denied**: Check that the workflow has write permissions
3. **Source not found**: Verify the v2 branch exists in sigmie/sigmie
4. **Trigger not working**: Ensure the PAT token has correct permissions