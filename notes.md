Etsy integration:
- [x] Get whsec_ signing secret from Webhook Portal → update ETSY_WEBHOOK_SECRET in prod .env
- [x] Update webhook URL in portal (remove ?secret=...)
- [x] Test webhook via Etsy's Testing tab
- [ ] Email notification on new order (not yet built)
- [x] Review sync — test the button in Admin → Etsy
- [ ] Product push to Etsy (observer for publish/update/delete)
- [ ] Shipment push (send tracking to Etsy when order marked shipped)
- [ ] Shipping profiles (needed before product push)

SEO (from prior audit, score 40/100):
- [x] Hero image WebP swap
- [ ] R2 cache headers