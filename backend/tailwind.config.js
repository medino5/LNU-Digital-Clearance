export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './storage/framework/views/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                'bg-app': '#F7F5EF',
                'bg-surface': '#FFFFFF',
                'bg-sidebar': '#0E2A47',
                'text-primary': '#1F2937',
                'text-muted': '#6B7280',
                'brand-navy': '#16345C',
                'brand-gold': '#D4A53A',
                'border-subtle': '#E7E3D8',
                success: {
                    surface: '#DCFCE7',
                    text: '#166534',
                },
                warning: {
                    surface: '#FEF3C7',
                    text: '#92400E',
                },
                info: {
                    surface: '#DBEAFE',
                    text: '#1E40AF',
                },
                danger: {
                    surface: '#FEE2E2',
                    text: '#991B1B',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04)',
            },
        },
    },
};
