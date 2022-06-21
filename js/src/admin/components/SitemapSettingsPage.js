import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';

export default class SitemapSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
  }

  content(vnode) {
    const currentMode = this.setting('fof-sitemap.mode')();

    // Change setting value client-side so the Select reflects which option is effectively used
    if (currentMode === 'cache' || currentMode === 'cache-disk') {
      this.setting('fof-sitemap.mode')('multi-file');
    }

    return [
      <div className="ExtensionPage-settings FoFSitemapSettingsPage">
        <div className="container">
          {app.forum.attribute('fof-sitemap.usersIndexAvailable')
            ? this.buildSettingComponent({
                type: 'switch',
                setting: 'fof-sitemap.excludeUsers',
                label: app.translator.trans('fof-sitemap.admin.settings.exclude_users'),
                help: app.translator.trans('fof-sitemap.admin.settings.exclude_users_help'),
              })
            : null}

          {this.modeChoice()}

          <hr />
          <h3>{app.translator.trans('fof-sitemap.admin.settings.advanced_options_label')}</h3>
          <div className="Form-group">
            {this.buildSettingComponent({
              type: 'select',
              setting: 'fof-sitemap.frequency',
              options: {
                hourly: app.translator.trans('fof-sitemap.admin.settings.frequency.hourly'),
                'twice-daily': app.translator.trans('fof-sitemap.admin.settings.frequency.twice_daily'),
                daily: app.translator.trans('fof-sitemap.admin.settings.frequency.daily'),
              },
              label: app.translator.trans('fof-sitemap.admin.settings.frequency_label'),
            })}
          </div>
          {this.submitButton(vnode)}
        </div>
      </div>,
    ];
  }

  modeChoice() {
    if (!app.forum.attribute('fof-sitemap.modeChoice')) {
      return null;
    }

    return (
      <div>
        {this.buildSettingComponent({
          type: 'select',
          setting: 'fof-sitemap.mode',
          options: {
            run: app.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
            'multi-file': app.translator.trans('fof-sitemap.admin.settings.modes.multi_file'),
          },
          label: app.translator.trans('fof-sitemap.admin.settings.mode_label'),
        })}

        <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help')}</p>

        <div>
          <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime_label')}</h3>
          <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime')}</p>
        </div>
        <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule')}</h4>
        <p>
          {app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule_setup', {
            a: <a href="https://docs.flarum.org/console/#schedulerun" target="_blank" rel="noopener"></a>,
          })}
        </p>
        <div>
          <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi_label')}</h3>
          <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi')}</p>
        </div>
      </div>
    );
  }
}
