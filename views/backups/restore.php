<style>

    .switch-disabled label:after {
        background-color: #e0e0e0;
    }
    .label-disabled {
        color: #e0e0e0;
    }
</style>

<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/backup-and-restore')">@lang('Backup And Restore')</a></li>
        <li class="uk-active"><span>@lang('Restore')</span></li>
    </ul>
</div>

<div class="uk-margin-top" riot-view>
    <form id="restore-form" class="uk-form uk-grid-gutter" onsubmit="{ submit }">

        <div if="{ backup && info && options }">
            <h3>@lang('Restore')</h3>
            <div class="uk-panel uk-panel-box uk-panel-card uk-margin">
                <div class="uk-text-small">{ info.description }</div>
                <div><strong><span class="uk-badge app-badge">{ backup }</span></strong></div>
            </div>
            <div class="uk-margin-top">
                <div class="uk-form-row">
                    <div class="uk-margin">
                        <label class="uk-text-medium">@lang('Full restore')</label>
                        <div class="uk-margin-small-top">
                            <field-boolean bind="fullRestore" label="false"></field-boolean>
                        </div>
                        <div class="uk-margin-top">
                            <label class="uk-clearfix uk-text-small uk-text-danger">
                                @lang('A full restore will remove all existing definitions and create new ones.')
                            </label>
                            <label class="uk-clearfix uk-text-small uk-text-danger">
                                @lang('A partial restore will update existing definitions and create new ones when they are new.')
                            </label>
                        </div>
                    </div>
                </div>

                <label class="uk-text-medium">@lang('Restore options')</label>
                <div ref="container" class="uk-form-row uk-margin-top" each="{option, index in options}" onclick="{ setOption }" style="cursor:pointer;">
                    <div class="uk-form-switch switch-{ !option.enabled ? 'disabled' : 'enabled' }">
                        <input ref="check" type="checkbox" id="{ option.key }" checked={option.value}  />
                        <label for="{ option.key }"></label>
                    </div>
                    <span class="label-{ !option.enabled ? 'disabled' : 'enabled' }">{ option.label }</span>
                </div>
            </div>
        </div>

        <div>
            <button class="uk-button uk-button-large uk-button-primary uk-margin-right">@lang('Restore')</button>
            <a href="@route('/backup-and-restore')">@lang('Cancel')</a>
        </div>

    </form>

    <div class="uk-modal">

        <div class="uk-modal-dialog uk-modal-dialog-large" if="{backup}">
            <h3>@lang('Restoring') { backup }</h3>

            <div class="uk-margin">
                <div class="uk-margin-small-left" if="{ restoring }">
                    <i class="uk-icon-spinner uk-icon-spin"></i> {App.i18n.get('Restoring...')}
                </div>
                <div class="uk-margin-small-left" if="{ !restoring }">
                    <i class="uk-icon-check-circle"></i> {App.i18n.get('Restore finished!')}
                </div>
            </div>

            <div class="uk-overflow-container">
                <div class="uk-alert">
                    <div class="uk-margin-small-left uk-margin-top" each="{ operation in operations }">
                        <i class="uk-icon-spinner uk-icon-spin" if="{ operation.status == 'running' }"></i>
                        <i class="uk-icon-check-circle uk-text-success" if="{ operation.status == 'success' }"></i>
                        <i class="uk-icon-info-circle uk-text-danger" if="{ operation.status == 'danger' }"></i>
                        <span class="uk-text-medium uk-text-{ operation.status }"> { operation.label }</span>
                    </div>
                </div>
            </div>

            <div class="uk-margin">
                <div class="uk-panel">
                    <a class="uk-button uk-button-large uk-button-primary uk-margin-right" href="@route('/backup-and-restore')" if="{ !restoring }">
                        @lang('Return to backups list')
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script type="view/script">

        var $this = this, modal, $root = App.$(this.root);

        this.mixin(RiotBindMixin);
        this.restoring = false;
        this.operations = [];
        this.backup = {{ json_encode($backup) }};
        this.info = {{ json_encode($info) }};
        this.fullRestore = true;

        this.options = [
          {'key': 'collections', 'label': 'Collections definitions', 'value': this.info.collections, 'enabled': this.info.collections},
          {'key': 'forms', 'label': 'Forms definitions', 'value': this.info.forms, 'enabled': this.info.forms},
          {'key': 'regions', 'label': 'Regions definitions', 'value': this.info.regions, 'enabled': this.info.regions},
          {'key': 'accounts', 'label': 'User accounts', 'value': this.info.accounts, 'enabled': this.info.accounts},
          {'key': 'webhooks', 'label': 'Webhooks', 'value': this.info.webhooks, 'enabled': this.info.webhooks},
          {'key': 'uploads', 'label': 'File uploads', 'value': this.info.uploads, 'enabled': this.info.uploads},
          {'key': 'entries', 'label': 'Include saved entries (collections, forms)', 'value': this.info.entries, 'enabled': this.info.entries},
          {'key': 'assets', 'label': 'Include saved assets', 'value': this.info.assets, 'enabled': this.info.assets},
          {'key': 'config', 'label': 'Global cockpit configuration', 'value': this.info.config, 'enabled': this.info.config}
        ];

        this.on('mount', function(){
            modal = UIkit.modal(App.$('.uk-modal', this.root), { modal: true, bgclose: false, keyboard: false });
        });

        submit(e) {
            if(e) e.preventDefault();

            App.ui.confirm("Are you sure?", function() {
                const settings = {
                    "backup": this.backup,
                    "fullRestore": this.fullRestore
                }
                const $this = this;
                const promises = [];
                this.restoring = true;
                modal.show();

                this.options.forEach(function(option) {
                    if (option.value) {
                        $this.operations.push({'name': option.key, 'label': option.label, 'status': 'running'});
                        promises.push(
                            App.request("/backup-and-restore/restorebackup/" + option.key, settings).then(function(data){
                                setTimeout(function() {
                                    $this.updateOperation(option.key, data.status || 'danger')
                                }, 500);
                            })
                        );
                        $this.update();
                    }
                });

                Promise.all(promises).then(function() {
                    // Clear caches.
                    App.request("/backup-and-restore/restorebackup/clearCaches", settings).then(function(data){
                        $this.operations.push({'name': 'clearCaches', 'label': 'Clear Caches', 'status': 'success'});
                        setTimeout(function() {
                            $this.restoring = false;
                            $this.update();
                            const errors = $this.operations.filter(function(operation) {
                                return operation.status !== 'success';
                            });
                            if (errors.length > 0) {
                                App.ui.notify('Operation finished with ' + errors.length  + ' errors!', 'danger');
                            } else {
                                App.ui.notify('Operation finished!', 'success');
                            }
                        }, 1000);
                    });
                }).catch(function(e) {
                    App.ui.notify('Operation finished with errors!', 'danger');
                    $this.restoring = false;
                    $this.update();
                });

            }.bind(this));

            return false;
        }

        updateOperation(name, status) {
            this.operations.forEach(function(operation, idx) {
                if (operation.name == name) {
                    $this.operations[idx].status = status;
                }
            });
            this.update();
        }

        setOption(e) {
            e.preventDefault();
            const idx = e.item.index;
            if (this.options[idx].key == 'entries' && !this.options[0].value) {
                return false;
            }
            if (this.options[idx].key == 'assets' && !this.options[5].value) {
                return false;
            }
            if (this.options[idx].enabled) {
                this.options[idx].value = !this.options[idx].value;
            }
            if (this.options[idx].key == 'uploads' && !this.options[idx].value) {
                this.options[7].value = false;
            }
            if (this.options[idx].key == 'collections' && !this.options[idx].value) {
                this.options[6].value = false;
            }
        }

    </script>

</div>
