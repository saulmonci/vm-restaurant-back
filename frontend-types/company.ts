// types/company.ts
/**
 * Company Types for Frontend Development
 * These types help maintain type safety when working with company slugs
 */

// Base company interface
export interface Company {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
}

// Company slugs enum for type safety and autocomplete
export enum CompanySlug {
    // Add your actual company slugs here
    RESTAURANT_DEMO = "restaurant-demo",
    PIZZA_PLACE = "pizza-place",
    BURGER_JOINT = "burger-joint",
    TACO_BELL = "taco-bell",
    SUSHI_BAR = "sushi-bar",
    COFFEE_SHOP = "coffee-shop",
}

// Constant array of valid company slugs
export const VALID_COMPANY_SLUGS = Object.values(CompanySlug);

// Type guard to check if a string is a valid company slug
export function isValidCompanySlug(slug: string): slug is CompanySlug {
    return VALID_COMPANY_SLUGS.includes(slug as CompanySlug);
}

// API Response types
export interface CompanySwitchResponse {
    message: string;
    company: {
        id: number;
        name: string;
        slug: string;
        // Add other company properties as needed
    };
}

export interface CompanyListResponse {
    companies: Company[];
}

// Development helper interface
export interface DevelopmentCompanyConfig {
    slug: CompanySlug;
    displayName: string;
    description?: string;
    features?: string[];
}

// Predefined development companies for easy testing
export const DEVELOPMENT_COMPANIES: DevelopmentCompanyConfig[] = [
    {
        slug: CompanySlug.RESTAURANT_DEMO,
        displayName: "Restaurant Demo",
        description: "Full-featured restaurant with all menu categories",
        features: ["delivery", "pickup", "happy-hour"],
    },
    {
        slug: CompanySlug.PIZZA_PLACE,
        displayName: "Mario's Pizza",
        description: "Pizza-focused restaurant",
        features: ["delivery", "pickup"],
    },
    {
        slug: CompanySlug.BURGER_JOINT,
        displayName: "Burger Joint",
        description: "Fast-food burger restaurant",
        features: ["pickup", "drive-through"],
    },
    {
        slug: CompanySlug.SUSHI_BAR,
        displayName: "Sakura Sushi",
        description: "Premium sushi restaurant",
        features: ["dine-in", "pickup"],
    },
    {
        slug: CompanySlug.COFFEE_SHOP,
        displayName: "Bean There Coffee",
        description: "Coffee shop with light meals",
        features: ["pickup", "dine-in"],
    },
];

// Helper function to get company config by slug
export function getCompanyConfig(
    slug: CompanySlug
): DevelopmentCompanyConfig | undefined {
    return DEVELOPMENT_COMPANIES.find((company) => company.slug === slug);
}

// Switch request interface
export interface CompanySwitchBySlugRequest {
    slug: CompanySlug;
}
