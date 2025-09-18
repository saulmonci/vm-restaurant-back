// components/CompanySwitcher.tsx
import React from "react";
import { CompanySlug, CompanySwitchResponse } from "../types/company";
import { useCompanySwitch } from "../hooks/useCompanySwitch";

// API service interface
interface ApiService {
    post<T>(url: string, data: any): Promise<T>;
    get<T>(url: string): Promise<T>;
}

export interface CompanySwitcherProps {
    apiService: ApiService;
    onCompanyChange?: (company: CompanySwitchResponse["company"]) => void;
    className?: string;
}

/**
 * Development component for easy company switching
 * Use this component in your development environment to quickly switch between companies
 */
export function CompanySwitcher({
    apiService,
    onCompanyChange,
    className = "company-switcher",
}: CompanySwitcherProps) {
    const { switchCompany, isLoading, error, currentCompany } =
        useCompanySwitch({
            apiService,
            onSuccess: (response) => {
                console.log("Company switched successfully:", response.company);
                onCompanyChange?.(response.company);
            },
            onError: (error) => {
                console.error("Failed to switch company:", error);
            },
        });

    const handleCompanySelect = async (
        event: React.ChangeEvent<HTMLSelectElement>
    ) => {
        const slug = event.target.value as CompanySlug;
        if (slug) {
            await switchCompany(slug);
        }
    };

    const formatSlugName = (slug: string) => {
        return slug.replace(/-/g, " ").replace(/\b\w/g, (l) => l.toUpperCase());
    };

    return (
        <div className={className}>
            <label htmlFor="company-select">Development Company:</label>
            <select
                id="company-select"
                onChange={handleCompanySelect}
                disabled={isLoading}
                value={currentCompany?.slug || ""}
            >
                <option value="">Select a company...</option>
                {Object.values(CompanySlug).map((slug) => (
                    <option key={slug} value={slug}>
                        {formatSlugName(slug)}
                    </option>
                ))}
            </select>

            {isLoading && <span className="loading">Switching...</span>}
            {error && <span className="error">Error: {error}</span>}
            {currentCompany && (
                <span className="current-company">
                    Current: {currentCompany.name} ({currentCompany.slug})
                </span>
            )}
        </div>
    );
}

// Simple CSS styles (you can move this to a separate CSS file)
export const companySwitcherStyles = `
  .company-switcher {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f9f9f9;
    max-width: 300px;
  }

  .company-switcher label {
    font-weight: bold;
    color: #333;
  }

  .company-switcher select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
  }

  .company-switcher select:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
  }

  .company-switcher .loading {
    color: #666;
    font-style: italic;
  }

  .company-switcher .error {
    color: #d32f2f;
    font-size: 14px;
  }

  .company-switcher .current-company {
    color: #2e7d32;
    font-size: 14px;
    font-weight: bold;
  }
`;

// Usage example component
export function DevelopmentToolbar({ apiService }: { apiService: ApiService }) {
    const handleCompanyChange = (company: CompanySwitchResponse["company"]) => {
        // You can add additional logic here, like updating global state
        console.log("Active company changed to:", company);

        // Example: Store in localStorage for persistence
        localStorage.setItem("dev-active-company", JSON.stringify(company));

        // Example: Trigger a page reload to refresh all data
        // window.location.reload();
    };

    return (
        <div
            style={{
                position: "fixed",
                top: "10px",
                right: "10px",
                zIndex: 1000,
                backgroundColor: "white",
                padding: "10px",
                borderRadius: "8px",
                boxShadow: "0 2px 8px rgba(0,0,0,0.1)",
            }}
        >
            <CompanySwitcher
                apiService={apiService}
                onCompanyChange={handleCompanyChange}
            />
        </div>
    );
}
