import http from '@/api/http';

export interface Organization {
    id: number;
    name: string;
    description: string | null;
    slug: string;
    avatar: string | null;
    owner: {
        id: number;
        name: string;
        email: string;
    };
    settings: {
        split_costs: boolean;
        auto_approve_members: boolean;
        default_member_role: string;
    };
    members_count: number;
    servers_count: number;
    created_at: string;
}

export interface OrganizationMember {
    id: number;
    user_id: number;
    role: 'owner' | 'admin' | 'member';
    joined_at: string;
    monthly_share_amount: number | null;
    payment_method: string | null;
    billing_email: string | null;
    last_payment_at: string | null;
    user: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
        created_at: string;
    };
}

export interface OrganizationInvitation {
    id: number;
    email: string;
    role: 'admin' | 'member';
    status: 'pending' | 'accepted' | 'declined' | 'expired';
    invited_by: {
        id: number;
        name: string;
        email: string;
    };
    message: string | null;
    expires_at: string;
    created_at: string;
}

export interface SplitBilling {
    enabled: boolean;
    total_monthly_cost: number;
    member_count: number;
    equal_share: number;
    members: Array<{
        user_id: number;
        name: string;
        email: string;
        role: string;
        custom_share: number | null;
        calculated_share: number;
        last_payment_at: string | null;
        is_overdue: boolean;
    }>;
    currency: string;
}

// Organizations
export const getOrganizations = (): Promise<{ data: Organization[] }> => {
    return http.get('/api/client/organizations');
};

export const getOrganization = (slug: string): Promise<{ data: Organization }> => {
    return http.get(`/api/client/organizations/${slug}`);
};

export const createOrganization = (data: {
    name: string;
    description?: string;
    slug?: string;
}): Promise<{ data: Organization }> => {
    return http.post('/api/client/organizations', data);
};

export const updateOrganization = (
    slug: string,
    data: {
        name?: string;
        description?: string;
    },
): Promise<{ data: Organization }> => {
    return http.patch(`/api/client/organizations/${slug}`, data);
};

export const deleteOrganization = (slug: string): Promise<void> => {
    return http.delete(`/api/client/organizations/${slug}`);
};

export const leaveOrganization = (slug: string): Promise<void> => {
    return http.post(`/api/client/organizations/${slug}/leave`);
};

// Settings
export const getOrganizationSettings = (slug: string): Promise<{ data: any }> => {
    return http.get(`/api/client/organizations/${slug}/settings`);
};

export const updateOrganizationSettings = (
    slug: string,
    data: {
        split_costs?: boolean;
        auto_approve_members?: boolean;
        default_member_role?: string;
    },
): Promise<{ data: any }> => {
    return http.patch(`/api/client/organizations/${slug}/settings`, data);
};

// Members
export const getOrganizationMembers = (slug: string): Promise<{ data: OrganizationMember[] }> => {
    return http.get(`/api/client/organizations/${slug}/members`);
};

export const updateMemberRole = (
    slug: string,
    memberId: number,
    role: string,
): Promise<{ data: OrganizationMember }> => {
    return http.patch(`/api/client/organizations/${slug}/members/${memberId}`, { role });
};

export const removeMember = (slug: string, memberId: number): Promise<void> => {
    return http.delete(`/api/client/organizations/${slug}/members/${memberId}`);
};

export const updateMemberBilling = (
    slug: string,
    memberId: number,
    data: {
        monthly_share_amount?: number;
        payment_method?: string;
        billing_email?: string;
    },
): Promise<{ data: OrganizationMember }> => {
    return http.patch(`/api/client/organizations/${slug}/members/${memberId}/billing`, data);
};

export const recordPayment = (slug: string, memberId: number): Promise<{ data: OrganizationMember }> => {
    return http.post(`/api/client/organizations/${slug}/members/${memberId}/payment`);
};

// Split Billing
export const getSplitBilling = (slug: string): Promise<{ data: SplitBilling }> => {
    return http.get(`/api/client/organizations/${slug}/split-billing`);
};

export const setCustomShares = (
    slug: string,
    shares: Array<{ user_id: number; amount: number }>,
): Promise<{ data: SplitBilling }> => {
    return http.post(`/api/client/organizations/${slug}/split-billing/shares`, { shares });
};

export const resetShares = (slug: string): Promise<{ data: SplitBilling }> => {
    return http.post(`/api/client/organizations/${slug}/split-billing/reset`);
};

// Invitations
export const getOrganizationInvitations = (slug: string): Promise<{ data: OrganizationInvitation[] }> => {
    return http.get(`/api/client/organizations/${slug}/invitations`);
};

export const inviteMember = (
    slug: string,
    data: {
        email: string;
        role?: string;
        message?: string;
    },
): Promise<{ data: OrganizationInvitation }> => {
    return http.post(`/api/client/organizations/${slug}/invitations`, data);
};

export const cancelInvitation = (slug: string, invitationId: number): Promise<void> => {
    return http.delete(`/api/client/organizations/${slug}/invitations/${invitationId}`);
};

export const resendInvitation = (slug: string, invitationId: number): Promise<{ data: OrganizationInvitation }> => {
    return http.post(`/api/client/organizations/${slug}/invitations/${invitationId}/resend`);
};

// User Invitations
export const getUserInvitations = (): Promise<{ data: OrganizationInvitation[] }> => {
    return http.get('/api/client/organizations/invitations');
};

export const acceptInvitation = (token: string): Promise<{ data: any }> => {
    return http.post(`/api/client/organizations/invitations/${token}/accept`);
};

export const declineInvitation = (token: string): Promise<{ data: any }> => {
    return http.post(`/api/client/organizations/invitations/${token}/decline`);
};
