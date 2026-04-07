export interface Shop {
    id: number;
    name: string;
    slug: string;
    category: string | null;
    rating: number;
    reviews_count: number;
    logo_image: string;
    cover_image?: string;
    status: string;
    status_class: string;
    is_active: boolean;
    is_verified: boolean;
}