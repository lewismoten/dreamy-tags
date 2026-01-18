(({
    serverSideRender,
    blocks: {
      registerBlockType
    },
    element: {
      createElement: el
    },
    blockEditor: {
      InspectorControls, 
      useBlockProps
    },
    components: {
      PanelBody,
      TextControl,
      TextareaControl,
      ToggleControl
    }
  }) => {

  const pluginName = 'dreamy-tag-cloud';
  const typeName = `lewismoten/${pluginName}`;

  const toNumbers = value => (value || "")
      .split(",")
      .map(s => s.trim())
      .filter(s => !isNaN(s))
      .filter(Boolean)
      .map(s => parseInt(s, 10));

  registerBlockType(typeName, {
    edit:  (props) => {
      const attrs = props.attributes;
      const blockProps = useBlockProps({ className: `${pluginName}-editor` });
      return [
        el('div', blockProps,
          el(InspectorControls, { key: "inspector" },
            el(PanelBody, { title: "Dreamy Tags Settings", initialOpen: true },

              el(TextControl, {
                label: "Title",
                value: attrs.title || "",
                onChange: (v) => props.setAttributes({ title: v }),
              }),

              el(TextareaControl, {
                label: "Filter Categories",
                help: "Example: 3, 9",
                value: attrs.cat_raw || "",
                onChange: (v) => props.setAttributes({ cat_raw: v }),
                onBlur: () =>
                  props.setAttributes({ cat: toNumbers(attrs.cat_raw) })
              }),

              el(TextareaControl, {
                label: "Filter Tags",
                value: attrs.tags_raw || '',
                onChange: (v) => props.setAttributes({ tags_raw: v }),
                onBlur:  () => props.setAttributes({ tags : toNumbers(attrs.tags_raw)})
              }),
              el(ToggleControl, {
                label: "Auto-exclude filtered",
                checked: !!attrs.exclude,
                onChange: (v) => props.setAttributes({ exclude: v }),
              }),
              el(TextareaControl, {
                label: "Exclude Tags",
                value: attrs.exclude_raw || '',
                onChange: (v) => props.setAttributes({ exclude_raw: v }),
                onBlur: () => props.setAttributes({ exclude: toNumbers(attrs.exclude_raw) }),
              })
            )
          )
        ),

        el(serverSideRender, {
          key: "preview",
          block: typeName,
          attributes: attrs,
        }),
      ];
    },

    save: () => null
  });
})(window.wp);
