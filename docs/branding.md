# Product branding

The shared `x-brand` component uses `public/images/brand/jobmetric-transparent.png`. This background-removed asset derives from `public/images/brand/jobmetric.png`, downloaded from the JobMetric GitHub organization avatar on 2026-09-15:
https://github.com/jobmetric
https://avatars.githubusercontent.com/u/225939223?s=512&v=4

Use the local asset for installation, authentication, panel navigation and favicon. Do not hotlink. User avatars use account initials. Product layout previews are structural diagrams without template logos. The product dashboard preserves the full ecommerce layout with clearly labeled preview figures. Demo-only actions remain excluded. Internal template CSS classes are compatibility details, not user-visible branding.

Dark mode applies the shared `brand-dark-contrast` SVG filter from `common.base` to `.cms-brand-mark`. It lifts navy strokes to a pale tone while retaining the green strokes and transparency, without duplicating the image. All CMS pages use this shared base and brand component.
