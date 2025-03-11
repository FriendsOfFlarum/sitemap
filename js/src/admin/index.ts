import app from 'flarum/admin/app';
import SitemapSettingsPage from './components/SitemapSettingsPage';

app.initializers.add('fof/sitemap', () => {
  app.extensionData.for('fof-sitemap').registerPage(SitemapSettingsPage);
});
