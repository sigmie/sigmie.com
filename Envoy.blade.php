@setup
    $repository = 'git@github.com:sigmie/sigmie.com.git';
    $appDirectory = '/home/forge/sigmie.com';
    $user = 'nico';
    $phpBinary = '/usr/bin/php8.3';
    $composerBinary = '/usr/local/bin/composer';
    $npmBinary = '/usr/bin/npm';
    $projectDirectory = exec('git rev-parse --show-toplevel');
    $docsDirectory = $projectDirectory . '/docs/v2';
@endsetup

@servers(['production' => 'nico@35.242.239.121', 'local' => '127.0.0.1'])

@story('deploy')
    sync_docs
    push_to_repository
    enable_maintenance_mode
    pull_changes
    run_composer
    run_migrations
    build_assets
    install_mcp_deps
    reindex_docs
    optimize_laravel
    restart_ssr
    restart_mcp
    reload_php_fpm
    warm_landing_cache
    disable_maintenance_mode
@endstory

@task('sync_docs', ['on' => 'local'])
    echo "Syncing v2 docs from sigmie/sigmie..."

    TEMP_DIR=$(mktemp -d)
    git clone --depth 1 --sparse git@github.com:sigmie/sigmie.git "$TEMP_DIR"
    cd "$TEMP_DIR"
    git sparse-checkout set docs

    if [ -d "$TEMP_DIR/docs" ]; then
        find {{ $docsDirectory }} -mindepth 1 -not -name '.gitkeep' -delete 2>/dev/null || true
        mkdir -p {{ $docsDirectory }}
        cp -r "$TEMP_DIR/docs/"* {{ $docsDirectory }}/
        echo "   ✅ Documentation files synced"
    else
        echo "   ⚠️ No docs directory found in sigmie/sigmie"
    fi

    rm -rf "$TEMP_DIR"

    cd {{ $projectDirectory }}
    git add docs/v2
    if ! git diff --staged --quiet; then
        git commit -m "chore: sync v2 documentation from sigmie/sigmie"
        echo "   ✅ Doc changes committed"
    else
        echo "   ℹ️  Docs already up to date"
    fi
@endtask

@task('push_to_repository', ['on' => 'local'])
    echo "Pushing changes to repository..."

    if ! git diff-index --quiet HEAD --; then
        echo ""
        echo "❌ You have uncommitted changes! Please commit them before deploying."
        git status --short
        exit 1
    fi

    LOCAL=$(git rev-parse @)
    REMOTE=$(git rev-parse @{u} 2>/dev/null)

    if [ "$LOCAL" != "$REMOTE" ]; then
        echo "   Pushing to remote repository..."
        git push

        if [ $? -ne 0 ]; then
            echo ""
            echo "❌ Failed to push to repository! Deployment aborted."
            exit 1
        fi

        echo "   ✅ Successfully pushed to repository!"
    else
        echo "   ℹ️  Repository is already up to date."
    fi
@endtask

@task('enable_maintenance_mode', ['on' => 'production'])
    echo "Enabling maintenance mode..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $phpBinary }} artisan down --retry=60
@endtask

@task('disable_maintenance_mode', ['on' => 'production'])
    echo "Disabling maintenance mode..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $phpBinary }} artisan up
    echo "✓ Application is now live!"
@endtask

@task('pull_changes', ['on' => 'production'])
    echo "Pulling latest changes..."
    cd {{ $appDirectory }}
    BEFORE_SHA=$(sudo -u forge git rev-parse HEAD)
    sudo -u forge git pull origin master
    AFTER_SHA=$(sudo -u forge git rev-parse HEAD)
    echo "$BEFORE_SHA" | sudo -u forge tee /tmp/sigmie-deploy-before-sha >/dev/null
    echo "$AFTER_SHA" | sudo -u forge tee /tmp/sigmie-deploy-after-sha >/dev/null
@endtask

@task('run_composer', ['on' => 'production'])
    echo "Installing composer dependencies..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $composerBinary }} install --no-dev --no-interaction --prefer-dist --optimize-autoloader
@endtask

@task('run_migrations', ['on' => 'production'])
    echo "Running migrations..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $phpBinary }} artisan migrate --force
@endtask

@task('build_assets', ['on' => 'production'])
    echo "Building assets..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $npmBinary }} install
    sudo -u forge {{ $npmBinary }} run build
@endtask

@task('install_mcp_deps', ['on' => 'production'])
    echo "Installing MCP server dependencies..."
    cd {{ $appDirectory }}/mcp-server
    sudo -u forge {{ $npmBinary }} install --omit=dev
@endtask

@task('reindex_docs', ['on' => 'production'])
    cd {{ $appDirectory }}

    if [ -f /tmp/sigmie-deploy-before-sha ] && [ -f /tmp/sigmie-deploy-after-sha ]; then
        BEFORE_SHA=$(cat /tmp/sigmie-deploy-before-sha)
        AFTER_SHA=$(cat /tmp/sigmie-deploy-after-sha)

        if [ "$BEFORE_SHA" = "$AFTER_SHA" ]; then
            echo "ℹ️  No commits pulled — skipping reindex."
            exit 0
        fi

        if sudo -u forge git diff --quiet "$BEFORE_SHA" "$AFTER_SHA" -- docs/; then
            echo "ℹ️  No doc changes in this deploy — skipping reindex."
            exit 0
        fi
    fi

    echo "Reindexing documentation..."
    sudo -u forge {{ $phpBinary }} artisan docs:index --fresh

    echo "Ensuring agent knowledge indices exist..."
    sudo -u forge {{ $phpBinary }} artisan sigmie:agent-tools:indices-create || true

    echo "Repopulating agent knowledge base..."
    sudo -u forge {{ $phpBinary }} artisan sigmie:agent-tools:kb-populate
@endtask

@task('optimize_laravel', ['on' => 'production'])
    echo "Optimizing Laravel..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $phpBinary }} artisan config:cache
    sudo -u forge {{ $phpBinary }} artisan route:cache
    sudo -u forge {{ $phpBinary }} artisan view:cache
    sudo -u forge {{ $phpBinary }} artisan optimize
@endtask

@task('restart_ssr', ['on' => 'production'])
    echo "Restarting SSR server..."
    sudo supervisorctl restart daemon-523660:daemon-523660_00
    sleep 3
    sudo supervisorctl status daemon-523660:daemon-523660_00
@endtask

@task('restart_mcp', ['on' => 'production'])
    echo "Restarting MCP server..."
    sudo supervisorctl restart sigmie-mcp:sigmie-mcp_00
    sleep 2
    sudo supervisorctl status sigmie-mcp:sigmie-mcp_00
@endtask

@task('reload_php_fpm', ['on' => 'production'])
    echo "Reloading PHP-FPM..."
    sudo systemctl reload php8.3-fpm
@endtask

{{--
    Production supervisor config required for the agent queue worker.
    The kb-populate command dispatches batched jobs that need a worker to drain,
    and SyncConversationTurnJob / GenerateTagsJob run async after each turn.

    /etc/supervisor/conf.d/sigmie-agent-queue.conf:

        [program:sigmie-agent-queue]
        process_name=%(program_name)s_%(process_num)02d
        command=/usr/bin/php8.3 /home/forge/sigmie.com/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
        autostart=true
        autorestart=true
        stopasgroup=true
        killasgroup=true
        user=forge
        numprocs=1
        redirect_stderr=true
        stdout_logfile=/home/forge/sigmie.com/storage/logs/agent-queue.log
        stopwaitsecs=3600

    Then: sudo supervisorctl reread && sudo supervisorctl update
--}}

@task('warm_landing_cache', ['on' => 'production'])
    echo "Warming landing-page cache..."
    cd {{ $appDirectory }}
    sudo -u forge {{ $phpBinary }} artisan landing:warm --fresh
@endtask
