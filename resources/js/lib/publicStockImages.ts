/**
 * Curated luxury residential marketing stills for the public site.
 * Hero, about, lifestyle, amenity, and CTA backgrounds must use these only —
 * never portfolio uploads or user media.
 */
const q = 'auto=format&fit=crop&w=1800&q=82';

const u = (id: string) => `https://images.unsplash.com/${id}?${q}`;

export const publicStockImages = {
    /** Full-bleed cinematic tower / compound */
    hero: u('photo-1545324418-cc1a3fa10c00'),
    /** Bright modern villa / residence for about */
    about: u('photo-1600596542815-ffad4c1539a9'),
    /** Lifestyle living room / terrace */
    lifestyle: u('photo-1600607687939-ce8a6c25118c'),
    /** Amenity pool / courtyard atmosphere */
    amenity: u('photo-1600585154340-be6161a56a0c'),
    /** Vision / craftsmanship interior detail */
    vision: u('photo-1613490493576-7fde63acd811'),
    /** Service panels — towers, interiors, compounds */
    services: [
        u('photo-1486406146926-c627a92ad1ab'),
        u('photo-1600607687644-c7171b42498f'), // was …98b (404) → working luxury interior
        u('photo-1512917774080-9991f1c4c750'),
        u('photo-1600566753190-17f0baa2a6c3'),
    ],
    /** Closing CTA skyline */
    cta: u('photo-1512453979798-5ea266f8880c'),
    /** Portfolio listing page banner (marketing, not DB) */
    workHero: u('photo-1560448204-e02f11c3d0e2'),
    /** Card / gallery placeholder when a project has no cover */
    fallback: u('photo-1560448204-e02f11c3d0e2'),
} as const;
