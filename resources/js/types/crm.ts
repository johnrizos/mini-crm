export type ContactStatus = 'lead' | 'prospect' | 'customer' | 'churned';

export type DealStage =
    | 'new'
    | 'qualified'
    | 'proposal'
    | 'negotiation'
    | 'won'
    | 'lost';

export type ActivityType = 'note' | 'call' | 'email' | 'meeting';

export type Option = {
    id: number;
    name: string;
};

export type Company = {
    id: number;
    name: string;
    domain: string | null;
    industry: string | null;
    contacts_count?: number;
    open_deals_value_cents?: number;
};

export type Contact = {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    phone: string | null;
    job_title: string | null;
    status: ContactStatus;
    company_id: number | null;
    company?: Option | null;
    created_at: string | null;
};

export type Deal = {
    id: number;
    title: string;
    value_cents: number;
    stage: DealStage;
    position: number;
    expected_close_date: string | null;
    closed_at: string | null;
    contact_id: number | null;
    company_id: number | null;
    contact?: { id: number; full_name: string } | null;
    company?: Option | null;
};

export type Activity = {
    id: number;
    type: ActivityType;
    body: string;
    happened_at: string;
    contact?: { id: number; full_name: string };
    deal?: { id: number; title: string } | null;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
    };
};
