<div>
    <ul class="uk-breadcrumb">
        <li class="uk-active"><span>@lang('Backup And Restore')</span></li>
    </ul>
</div>

<div class="uk-margin-top" riot-view>

    @if($app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.view'))
    <div class="uk-form uk-clearfix" show="{!loading}">

        <span class="uk-form-icon">
            <i class="uk-icon-filter"></i>
            <input type="text" class="uk-form-large uk-form-blank" ref="txtfilter" placeholder="@lang('Filter by name...')" onchange="{ updatefilter }">
        </span>

        <div class="uk-float-right">
            @if($app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.create'))
            <span class="uk-button uk-button-primary uk-button-large uk-form-file">
                <input class="js-upload-select" type="file" multiple="true">
                <i class="uk-icon-upload uk-icon-justify"></i> @lang('Upload')
            </span>
            <a class="uk-button uk-button-primary uk-button-large" href="@route('/backup-and-restore/create')">
                <i class="uk-icon-plus-circle uk-icon-justify"></i> @lang('Backup')
            </a>
            @endif
        </div>

    </div>
    @endif

    <div class="uk-text-xlarge uk-text-center uk-text-primary uk-margin-large-top" show="{ loading }">
        <i class="uk-icon-spinner uk-icon-spin"></i>
    </div>

    <div class="uk-text-large uk-text-center uk-margin-large-top uk-text-muted" show="{ !loading && !backups.length }">
        <img class="uk-svg-adjust" src="@url('assets:app/media/icons/database.svg')" width="100" height="100" alt="@lang('Backups')" data-uk-svg />
        <p>@lang('No backups found')</p>
    </div>

    <table class="uk-table uk-table-tabbed uk-table-striped uk-margin-top" if="{ ready && !loading && backups.length }">
        <thead>
            <tr>
                <th class="uk-text-small" data-sort="filename">
                    <a class="uk-link-muted uk-noselect {sortedBy == 'filename' && 'uk-text-primary'}">
                        @lang('Filename') <span if="{sortedBy == 'filename'}" class="uk-icon-long-arrow-{ sortedOrder == -1 ? 'up':'down'}"></span>
                    </a>
                </th>
                <th class="uk-text-small" width="40%" data-sort="description">
                    <a class="uk-link-muted uk-noselect {sortedBy == 'description' && 'uk-text-primary'}">
                        @lang('Description') <span if="{sortedBy == 'description'}" class="uk-icon-long-arrow-{ sortedOrder == -1 ? 'up':'down'}"></span>
                    </a>
                </th>
                <th class="uk-text-small" width="80" data-sort="created">
                      @lang('Definitions')
                </th>
                <th class="uk-text-small" width="100" data-sort="size">
                    <a class="uk-link-muted uk-noselect {sortedBy == 'size' && 'uk-text-primary'}">
                        @lang('Size') <span if="{sortedBy == 'size'}" class="uk-icon-long-arrow-{ sortedOrder == -1 ? 'up':'down'}"></span>
                    </a>
                </th>
                <th class="uk-text-small" width="100" data-sort="created">
                    <a class="uk-link-muted uk-noselect {sortedBy == 'created' && 'uk-text-primary'}">
                        @lang('Created') <span if="{sortedBy == 'created'}" class="uk-icon-long-arrow-{ sortedOrder == -1 ? 'up':'down'}"></span>
                    </a>
                </th>
                <th width="20"></th>
            </tr>
        </thead>
        <tbody>
            <tr each="{backup, $index in backups}" if="{ infilter(backup) }">
                <td>
                    <a class="uk-link-muted uk-text-small" href="@route('/backup-and-restore/view')/{ backup.filename }" title="@lang('View details')">
                        { backup.filename }
                    </a>
                </td>
                <td class="uk-text-truncate">{ backup.description }</td>
                <td class="uk-text-small">
                  <raw content="{ App.Utils.renderValue('tags', this.parent.getDefinitions(backup.definitions)) }"></raw>
                </td>
                <td class="uk-text-small">{ App.Utils.formatSize(backup.size) }</td>
                <td>
                    <span class="uk-badge uk-badge-outline uk-text-muted" title="{ App.Utils.dateformat( new Date( 1000 * backup.created ), 'YYYY-MM-DD HH:mm:ss') }" data-uk-tooltip="pos:'top'">
                        { App.Utils.dateformat( new Date( 1000 * backup.created ), 'MMM DD, YYYY') }
                    </span>
                </td>
                <td>
                    <span data-uk-dropdown="pos:'bottom-right'">

                        <a class="uk-icon-bars"></a>

                        <div class="uk-dropdown">
                            <ul class="uk-nav uk-nav-dropdown uk-dropdown-close">
                                <li class="uk-nav-header">@lang('Actions')</li>
                                <li><a href="@route('/backup-and-restore/view')/{ backup.filename }">@lang('View')</a></li>
                                @if($app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.restore'))
                                <li><a href="@route('/backup-and-restore/restore')/{ backup.filename }">@lang('Restore')</a></li>
                                @endif
                                @if($app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.download'))
                                <li><a onclick="{ parent.download }">@lang('Download')</a></li>
                                @endif
                                @if($app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.delete'))
                                <li class="uk-nav-item-danger"><a onclick="{ this.parent.remove }">@lang('Delete')</a></li>
                                @endif
                            </ul>
                        </div>
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="uk-margin uk-flex uk-flex-middle" if="{ !loading && pages > 1 }">

        <ul class="uk-breadcrumb uk-margin-remove">
            <li class="uk-active"><span>{ page }</span></li>
            <li data-uk-dropdown="mode:'click'">

                <a><i class="uk-icon-bars"></i> { pages }</a>

                <div class="uk-dropdown">

                    <strong class="uk-text-small">@lang('Pages')</strong>

                    <div class="uk-margin-small-top { pages > 5 ? 'uk-scrollable-box':'' }">
                        <ul class="uk-nav uk-nav-dropdown">
                            <li class="uk-text-small" each="{k,v in new Array(pages)}"><a class="uk-dropdown-close" onclick="{ parent.loadpage.bind(parent, v+1) }">@lang('Page') {v + 1}</a></li>
                        </ul>
                    </div>
                </div>

            </li>
        </ul>

        <div class="uk-button-group uk-margin-small-left">
            <a class="uk-button uk-button-small" onclick="{ loadpage.bind(this, page-1) }" if="{page-1 > 0}">@lang('Previous')</a>
            <a class="uk-button uk-button-small" onclick="{ loadpage.bind(this, page+1) }" if="{page+1 <= pages}">@lang('Next')</a>
        </div>

    </div>

    <script type="view/script">

        var $this = this, limit = 20;

        this.backups  = [];
        this.current  = 0;
        this.filter   = '';
        this.sort     = {'created': 1};
        this.page     = 1;
        this.count    = 0;

        this.loading  = true;
        this.ready    = false;

        this.backupsPath = {{ json_encode($backupsPath) }};

        this.on('mount', function() {

            App.$(this.root).on('click', '[data-sort]', function() {
                $this.updatesort(this.getAttribute('data-sort'));
            });

            App.assets.require(['/assets/lib/uikit/js/components/upload.js'], function() {
                var uploadSettings = {
                        action: App.route('/media/api'),
                        params: { "cmd": "upload" },
                        type: 'json',
                        before: function(options) {
                            options.params.path = $this.backupsPath;
                        },
                        loadstart: function() {
                        },
                        progress: function(percent) {
                        },
                        allcomplete: function(response) {
                            if (response && response.failed && response.failed.length) {
                                App.ui.notify("Backup failed to uploaded.", "danger");
                            }

                            if (!response) {
                                App.ui.notify("Something went wrong.", "danger");
                            }

                            if (response && response.uploaded && response.uploaded.length) {
                                App.ui.notify("Backup uploaded.", "success");
                                $this.load();
                            }

                        }
                },

                uploadselect = UIkit.uploadSelect(App.$('.js-upload-select', $this.root)[0], uploadSettings);

                UIkit.init(this.root);
            });


            this.load();
        });

        remove(evt) {
            var backup = evt.item.backup;
            App.ui.confirm("Are you sure?", function() {
                App.request('/backup-and-restore/delete/' + backup.filename).then(function(data){
                    App.ui.notify("Backup removed", "success");
                    $this.backups.splice(evt.item.$index, 1);
                    $this.update();
                });
            });
        }

        updatefilter() {

            var load = this.filter ? true : false;

            this.filter = this.refs.txtfilter.value || null;

            if (this.filter || load) {
                this.backups = [];
                this.loading = true;
                this.page = 1;
                this.load();
            }
        }

        infilter(backup) {
            var filename = backup.filename.toLowerCase();
            return (!this.filter || (filename && filename.indexOf(this.filter) !== -1));
        }

        updatesort(field) {

            if (!field) {
                return;
            }

            var col = field;

            if (!this.sort[col]) {
                this.sort      = {};
                this.sort[col] = 1;
            } else {
                this.sort[col] = this.sort[col] == 1 ? -1 : 1;
            }

            this.sortedBy = field;
            this.sortedOrder = this.sort[col];

            this.backups = [];

            this.load();
        }

        load() {

            var options = { sort:this.sort };

            if (this.filter) {
                options.filter = this.filter;
            }

            options.limit = limit;
            options.skip  = (this.page - 1) * limit;

            this.loading = true;

            return App.request('/backup-and-restore/find', {options:options}).then(function(data){
                this.backups  = data.backups;
                this.pages    = data.pages;
                this.page     = data.page;
                this.count    = data.count;
                this.ready    = true;
                this.loadmore = data.backups.length && data.backups.length == limit;
                this.loading = false;

                this.update();

            }.bind(this))
        }

        loadpage(page) {
            this.page = page > this.pages ? this.pages:page;
            this.load();
        }

        getDefinitions(definitions) {
          const active = [];

          Object.keys(definitions).forEach( name => {
            if (definitions[name]) {
              active.push(name);
            }
          });
          return active;
        }

        download(e, item) {
            e.stopPropagation();
            item = e.item.backup;

            window.open(App.route('/backup-and-restore/download/' + item.filename));
        }

    </script>

</div>
