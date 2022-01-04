import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';

export default class SitemapSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
  }

  content() {
    return [
      <div className="container">
        <div className="FoFSitemapSettingsPage">
          <div className="Form-group">
            {this.buildSettingComponent({
              type: 'select',
              setting: 'fof-sitemap.mode',
              options: {
                run: app.translator.trans('fof-sitemap.admin.settings.modes.runtime'),
                cache: app.translator.trans('fof-sitemap.admin.settings.modes.cache'),
                'cache-disk': app.translator.trans('fof-sitemap.admin.settings.modes.cache_disk'),
                'multi-file': app.translator.trans('fof-sitemap.admin.settings.modes.multi_file'),
              },
              label: app.translator.trans('fof-sitemap.admin.settings.mode_label'),
            })}
          </div>

          <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help')}</p>

          <div>
            <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime_label')}</h3>
            <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_runtime')}</p>
          </div>
          <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule')}</h4>
          <p>
            {app.translator.trans('fof-sitemap.admin.settings.mode_help_schedule_setup', {
              a: <a href="https://docs.flarum.org/console.html#schedule-run" target="_blank"></a>,
            })}
          </p>
          <div>
            <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_cache_disk_label')}</h3>
            <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_cache_disk')}</p>
          </div>
          <h4>{app.translator.trans('fof-sitemap.admin.settings.mode_help_large')}</h4>
          <div>
            <h3>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi_label')}</h3>
            <p>{app.translator.trans('fof-sitemap.admin.settings.mode_help_multi')}</p>
          </div>
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
          {this.submitButton()}
        </div>
      </div>,
    ];
  }
}
