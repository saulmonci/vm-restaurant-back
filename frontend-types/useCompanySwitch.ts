// hooks/useCompanySwitch.ts
import { useState, useCallback } from "react";
import {
    CompanySlug,
    CompanySwitchResponse,
    isValidCompanySlug,
} from "../types/company";

// API service interface
interface ApiService {
    post<T>(url: string, data: any): Promise<T>;
    get<T>(url: string): Promise<T>;
}

// Hook configuration
interface UseCompanySwitchConfig {
    apiService: ApiService;
    onSuccess?: (response: CompanySwitchResponse) => void;
    onError?: (error: Error) => void;
}

// Hook return type
interface UseCompanySwitchReturn {
    switchCompany: (slug: CompanySlug) => Promise<boolean>;
    isLoading: boolean;
    error: string | null;
    currentCompany: CompanySwitchResponse["company"] | null;
}

/**
 * Custom hook for switching between companies in development
 * Provides type-safe company switching with loading states
 */
export function useCompanySwitch(
    config: UseCompanySwitchConfig
): UseCompanySwitchReturn {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [currentCompany, setCurrentCompany] = useState<
        CompanySwitchResponse["company"] | null
    >(null);

    const switchCompany = useCallback(
        async (slug: CompanySlug): Promise<boolean> => {
            if (!isValidCompanySlug(slug)) {
                const errorMsg = `Invalid company slug: ${slug}`;
                setError(errorMsg);
                config.onError?.(new Error(errorMsg));
                return false;
            }

            setIsLoading(true);
            setError(null);

            try {
                const response =
                    await config.apiService.post<CompanySwitchResponse>(
                        "/api/company/switch-by-slug",
                        { slug }
                    );

                setCurrentCompany(response.company);
                config.onSuccess?.(response);
                return true;
            } catch (err) {
                const errorMsg =
                    err instanceof Error
                        ? err.message
                        : "Failed to switch company";
                setError(errorMsg);
                config.onError?.(
                    err instanceof Error ? err : new Error(errorMsg)
                );
                return false;
            } finally {
                setIsLoading(false);
            }
        },
        [config]
    );

    return {
        switchCompany,
        isLoading,
        error,
        currentCompany,
    };
}
