-- Add effect settings column to themes table
ALTER TABLE themes ADD COLUMN effect_settings JSON DEFAULT NULL AFTER custom_css;

-- Update existing themes with default effect settings
UPDATE themes SET effect_settings = JSON_OBJECT(
    'button_glow', JSON_OBJECT('enabled', true, 'intensity', 'medium'),
    'hover_animations', JSON_OBJECT('enabled', true, 'speed', 'normal'),
    'background_animation', JSON_OBJECT('enabled', true, 'style', 'floating-shapes'),
    'page_transitions', JSON_OBJECT('enabled', true, 'style', 'fade-up'),
    'shimmer_effects', JSON_OBJECT('enabled', true),
    'shadow_style', 'soft',
    'border_radius', 'rounded',
    'card_hover', JSON_OBJECT('enabled', true, 'style', 'lift')
);
