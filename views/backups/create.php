<div>
    <ul class="uk-breadcrumb">
        <li><a href="@route('/backup-and-restore')">@lang('Backup And Restore')</a></li>
        <li class="uk-active"><span>@lang('Create')</span></li>
    </ul>
</div>

<div class="uk-margin-top" riot-view>
    <form id="account-form" class="uk-form uk-grid uk-grid-gutter" onsubmit="{ submit }">

        <h3>@lang('Create new backup')</h3>
        <div class="uk-width-medium-1-1">

            <div class="uk-form-row">
              <label class="uk-text-small">@lang('Description')</label>
              <input class="uk-width-1-1 uk-form-large" type="text" bind="description" autocomplete="off" required>
            </div>

            <h3>@lang('Backup Options')</h3>
            <div ref="container" class="uk-form-row" each="{option, index in options}" onclick="{ setOption }" style="cursor:pointer;">
                <div class="uk-form-switch">
                    <input ref="check" type="checkbox" id="{ option.key }" checked={option.value} />
                    <label for="{ option.key }"></label>
                </div>
                <span>{ option.label }</span>
            </div>
        </div>

        <div class="uk-width-medium-1-2">
            <button class="uk-button uk-button-large uk-width-1-3 uk-button-primary uk-margin-right">@lang('Create')</button>
            <a href="@route('/backup-and-restore')">@lang('Cancel')</a>
        </div>

    </form>


    <script type="view/script">

        var $this = this, $root = App.$(this.root);

        this.mixin(RiotBindMixin);

        this.description = "@lang('Manual backup created on') " + App.Utils.dateformat(new Date(), 'MMM DD, YYYY HH:mm');

        this.options = [
          {'key': 'config', 'label': 'Global cockpit configuration', 'value': true},
          {'key': 'collections', 'label': 'Collections definitions', 'value': true},
          {'key': 'forms', 'label': 'Forms definitions', 'value': true},
          {'key': 'regions', 'label': 'Regions definitions', 'value': true},
          {'key': 'accounts', 'label': 'User accounts', 'value': true},
          {'key': 'webhooks', 'label': 'Webhooks definitions', 'value': true},
          {'key': 'entries', 'label': 'Include collection entries', 'value': true},
          {'key': 'assets', 'label': 'Assets', 'value': true},
          {'key': 'uploads', 'label': 'File uploads', 'value': true}
        ];

        this.on('mount', function(){
            // bind clobal command + save
            Mousetrap.bindGlobal(['command+s', 'ctrl+s'], function(e) {
                e.preventDefault();
                $this.submit();
                return false;
            });

            $this.update();
        });

        submit(e) {
            if(e) e.preventDefault();

            App.request("/backup-and-restore/save", {"description": this.description, "options": this.options}).then(function(data){
                $this.backup = data;
                App.ui.notify("Backup created", "success");
                setTimeout(function() {
                  location.href = '/backup-and-restore';
                }, 1000)
            });

            return false;
        }

        setOption(e) {
            e.preventDefault();
            const idx = e.item.index;
            if (this.options[idx].key == 'entries' && !this.options[1].value) {
                return false;
            }
            if (this.options[idx].key == 'assets' && !this.options[8].value) {
                return false;
            }
            this.options[idx].value = !this.options[idx].value;
            if (this.options[idx].key == 'uploads' && !this.options[idx].value) {
                this.options[7].value = false;
            }
            if (this.options[idx].key == 'collections' && !this.options[idx].value) {
                this.options[6].value = false;
            }
        }

    </script>

</div>
