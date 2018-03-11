<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/backup-and-restore')">@lang('Backup And Restore')</a></li>
        <li class="uk-active"><span>@lang('Details')</span></li>
    </ul>
</div>

<div class="uk-margin-top uk-grid" riot-view>

    <div class="uk-width-medium-4-5 uk-row-first">
        <p><strong><span class="uk-badge app-badge">@lang('Backup details')</span></strong></p>

        <table class="uk-table uk-table-striped">
            <tbody>
                <tr>
                    <td>@lang('Description')</td>
                    <td>{ info.description }</td>
                </tr>
                <tr>
                    <td>@lang('Created')</td>
                    <td>{ App.Utils.dateformat( new Date( 1000 * info.created ), 'YYYY-MM-DD HH:mm:ss') }</td>
                </tr>
                <tr>
                    <td>@lang('Config')</td>
                    <td>
                        <div if="{ !info.config }">@lang('No configuration saved')</div>
                        <div if="{ info.config }">
                            <pre show="{ showConfig }">
                            { config }
                            </pre>
                            <a class="uk-button uk-button-small" onclick="{ toggleConfig.bind(this, true) }" show="{ !showConfig }">
                                <i class="uk-icon-cog"></i> @lang('Show Configuration')
                            </a>
                            <a class="uk-button uk-button-small" onclick="{ toggleConfig.bind(this, false) }" show="{ showConfig }">
                                <i class="uk-icon-cog"></i> @lang('Hide')
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Collections')</td>
                    <td>
                        <div if="{ !info.collections }">@lang('No collections saved')</div>
                        <span class="uk-badge uk-margin-small-left" each="{ collection in collections }" if="{ info.collections && collections.length }">
                            { collection.label || collection.name }
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Regions')</td>
                    <td>
                        <div if="{ !info.regions }">@lang('No regions saved')</div>
                        <span class="uk-badge uk-margin-small-left" each="{ region in regions }" if="{ info.regions && regions.length }">
                            { region }
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Forms')</td>
                    <td>
                        <div if="{ !info.forms }">@lang('No forms saved')</div>
                        <span class="uk-badge uk-margin-small-left" each="{ form in forms }" if="{ info.forms && forms.length }">
                            { form }
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Accounts')</td>
                    <td>
                        <div if="{ !info.accounts }">@lang('No accounts saved')</div>
                        <span class="uk-badge uk-margin-small-left" each="{ account in accounts }" if="{ info.accounts && accounts.length }">
                            { account }
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Webhooks')</td>
                    <td>
                        <div if="{ !info.accounts }">@lang('No webhooks saved')</div>
                        <span class="uk-badge uk-margin-small-left" each="{ webhook in webhooks }" if="{ info.webhooks && webhooks.length }">
                            { webhook }
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Entries')</td>
                    <td>
                        <div if="{ !info.entries }">@lang('No content entries saved')</div>
                        <div if="{ info.entries }">
                            <div class="uk-margin-small-left" each="{ collection in collections }">
                                { collection.label || collection.name }: <strong class="uk-text-small">{ collection.count }</strong>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Assets')</td>
                    <td>
                        <div if="{ !info.entries }">@lang('No assets saved')</div>
                        <div if="{ info.entries }">
                            <div class="uk-margin-small-left" each="{ asset in assets }">
                                { asset.title } <strong class="uk-text-small">{ asset.mime }</strong>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>@lang('Uploads')</td>
                    <td>
                        <div if="{ !info.uploads }">@lang('No uploads saved')</div>
                        <div if="{ info.uploads }">
                            <div class="uk-margin-small-left" each="{ upload in uploads }">
                                { upload.file }: <strong class="uk-text-small">{ upload.size }</strong>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="uk-width-small-1-5">
        <ul class="uk-nav">
            <li class="uk-nav-header">@lang('Actions')</li>
            <li>
                <a class="uk-button uk-button-small uk-button-primary" href="@route('/backup-and-restore/restore/{ filename }')"><i class="uk-icon-refresh"></i> @lang('Restore')</a>
            </li>
            <li class="uk-nav-divider"></li>
            <li>
                <a class="uk-button uk-button-small" onclick="{ download }">@lang('Download')</a>
            </li>
            <li class="uk-nav-divider"></li>
            <li>
                <a class="uk-button uk-button-small" href="@route('/backup-and-restore')">@lang('Cancel')</a>
            </li>
            <li class="uk-nav-divider"></li>
            <li>
                <a class="uk-button uk-button-small uk-text-danger" onclick="{ remove }"><i class="uk-icon-trash-o"></i> @lang('Delete')</a>
            </li>
        </ul>
    </div>

    <script type="view/script">
        var $this = this;

        this.showConfig = false;
        this.filename = {{ json_encode($filename) }};
        this.info = {{ json_encode($info) }};
        this.config = {{ json_encode($config) }};
        this.collections = {{ json_encode($collections) }};
        this.regions = {{ json_encode($regions) }};
        this.forms = {{ json_encode($forms) }};
        this.accounts = {{ json_encode($accounts) }};
        this.webhooks = {{ json_encode($webhooks) }};
        this.entries = {{ json_encode($entries) }};
        this.uploads = {{ json_encode($uploads) }};
        this.assets = {{ json_encode($assets) }};

        download(e) {
            e.stopPropagation();

            window.open(App.route('/backup-and-restore/download/' + this.filename));
        }

        toggleConfig(status) {
            this.showConfig = status;
        }

        remove(e) {
            App.ui.confirm("Are you sure?", function() {
                App.request('/backup-and-restore/delete/' + $this.filename).then(function(data) {
                    App.ui.notify("Entry removed", "success");
                    setTimeout(function() {
                        location.href = '/backup-and-restore';
                    }, 1000)
                });
            }.bind(this));
        }

    </script>

</div>
