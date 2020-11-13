import app from 'flarum/app';
import SitemapSettingsModal from './components/SitemapSettingsModal';

app.initializers.add('fof/sitemap', () => {
    app.extensionSettings['fof-sitemap'] = () => app.modal.show(SitemapSettingsModal);
});
