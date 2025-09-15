# How to Trigger Docs Sync from sigmie/sigmie

Since cross-repository workflow triggers have limitations with the default GITHUB_TOKEN, here are the working options:

## Option 1: Manual Trigger (Simplest)
After pushing docs changes to sigmie/sigmie v2 branch:
1. Go to https://github.com/sigmie/sigmie.com/actions
2. Click "Sync V2 Documentation" workflow
3. Click "Run workflow"

## Option 2: Using GitHub App or PAT (Automated)
Create a PAT or GitHub App with repo permissions:

1. **Create PAT**:
   - Go to https://github.com/settings/tokens
   - Generate new token with `repo` scope
   - Name it: "Sigmie Docs Sync"

2. **Add to sigmie/sigmie secrets**:
   - Go to sigmie/sigmie Settings > Secrets
   - Add secret: `SIGMIE_COM_TOKEN`

3. **Add this workflow to sigmie/sigmie**:

```yaml
name: Trigger Docs Sync

on:
  push:
    branches: [v2]
    paths: ['docs/**']

jobs:
  trigger-sync:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger sync via dispatch
        run: |
          curl -X POST \
            -H "Authorization: token ${{ secrets.SIGMIE_COM_TOKEN }}" \
            -H "Accept: application/vnd.github.v3+json" \
            https://api.github.com/repos/sigmie/sigmie.com/dispatches \
            -d '{"event_type":"sync-v2-docs"}'
```

## Option 3: Using Push to Special Branch (Creative Workaround)
From sigmie/sigmie, push an empty commit to a special branch:

```yaml
name: Trigger Docs Sync

on:
  push:
    branches: [v2]
    paths: ['docs/**']

jobs:
  trigger-sync:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout sigmie.com
        uses: actions/checkout@v4
        with:
          repository: sigmie/sigmie.com
          token: ${{ github.token }}
          
      - name: Create trigger branch
        run: |
          git config user.name "github-actions[bot]"
          git config user.email "github-actions[bot]@users.noreply.github.com"
          
          # Create or update trigger branch
          BRANCH="sync-trigger-$(date +%s)"
          git checkout -b $BRANCH
          git push origin $BRANCH
          
          # Clean up old trigger branches
          git push origin --delete $BRANCH || true
```

## Option 4: Webhook Service (External)
Use a webhook service like Zapier, IFTTT, or a custom webhook:
1. Set up webhook to listen for sigmie/sigmie pushes
2. Webhook triggers GitHub API to start sigmie.com workflow

## Recommended Approach

For simplicity and security, we recommend:
- **Daily automatic sync** (already configured)
- **Manual trigger** when immediate sync is needed
- **PAT with minimal permissions** if full automation is required

The daily sync at 2 AM UTC should be sufficient for most documentation updates.