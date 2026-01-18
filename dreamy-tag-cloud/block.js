(({
  serverSideRender,
  blocks: { registerBlockType },
  element: { createElement: el, useState },
  blockEditor: { InspectorControls, useBlockProps },
  components: {
    PanelBody,
    TextControl,
    TextareaControl,
    ToggleControl,
    ComboboxControl,
    Button
  },
  data: { useSelect },
  coreData: { store: coreStore }
}) => {

  const pluginName = 'dreamy-tag-cloud';
  const typeName = `lewismoten/${pluginName}`;

  const asNumber = (value) => {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
  };

  const toNumbers = (value) => (value || "")
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean)
    .map((s) => Number(s))
    .filter((n) => Number.isFinite(n))
    .map((n) => parseInt(n, 10));

  registerBlockType(typeName, {
    edit: (props) => {
      const attrs = props.attributes || {};
      const blockProps = useBlockProps({ className: `${pluginName}-editor` });

      const [tagSearch, setTagSearch] = useState("");

      const allTags = useSelect(
        (select) =>
          select(coreStore).getEntityRecords("taxonomy", "post_tag", {
            search: tagSearch || undefined,
            per_page: 50,
            hide_empty: false,
            orderby: "name",
            order: "asc"
          }),
        [tagSearch]
      );

      const tagOptions = (allTags || []).map((t) => ({
        label: t.name,
        value: t.id
      }));

      const selectedTagIds = Array.isArray(attrs.tags)
        ? attrs.tags.map(asNumber).filter((n) => n !== null)
        : [];

      const selectedTags = useSelect(
        (select) => {
          if (!selectedTagIds.length) return [];
          return select(coreStore).getEntityRecords("taxonomy", "post_tag", {
            include: selectedTagIds,
            per_page: selectedTagIds.length,
            hide_empty: false
          });
        },
        [selectedTagIds.join(",")]
      );
      const selectedTagNameById = {};
      (selectedTags || []).forEach((t) => {
        selectedTagNameById[t.id] = t.name;
      });

      const removeTag = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        props.setAttributes({
          tags: selectedTagIds.filter((t) => t !== idNum)
        });
      };

      const addTag = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        if (!selectedTagIds.includes(idNum)) {
          props.setAttributes({
            tags: [...selectedTagIds, idNum]
          });
        }
      };

      return el(
        "div",
        blockProps,

        el(
          InspectorControls,
          { key: "inspector" },
          el(
            PanelBody,
            { title: "Dreamy Tags Settings", initialOpen: true },

            el(TextControl, {
              label: "Title",
              value: attrs.title || "",
              onChange: (v) => props.setAttributes({ title: v })
            }),

            el(TextareaControl, {
              label: "Filter Categories (IDs)",
              help: "Example: 3, 9",
              value: attrs.cat_raw || "",
              onChange: (v) => props.setAttributes({ cat_raw: v }),
              onBlur: () => props.setAttributes({ cat: toNumbers(attrs.cat_raw) })
            }),

            el(ComboboxControl, {
              label: "Filter Tags",
              help: "Type to search tags, then click to add",
              options: tagOptions,
              value: null,
              onFilterValueChange: (input) => setTagSearch(input || ""),
              onChange: (tagId) => addTag(tagId)
            }),

            el(
              "div",
              { style: { marginTop: "8px" } },
              selectedTagIds.length
                ? el(
                  "ul",
                  { style: { margin: 0, paddingLeft: "18px" } },
                  selectedTagIds.map((id) => {
                    const label = selectedTagNameById[id] || `Tag #${id}`;
                    return el(
                      "li",
                      { key: id, style: { display: "flex", gap: "8px", alignItems: "center" } },
                      el("span", null, label),
                      el(
                        Button,
                        {
                          isDestructive: true,
                          isSmall: true,
                          type: "button",
                          onClick: (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            removeTag(id);
                          }
                        },
                        "×"
                      )
                    );
                  })
                )
                : el("div", { style: { opacity: 0.7 } }, "No tags selected yet.")
            ),

            el(ToggleControl, {
              label: "Auto-exclude filtered tags",
              checked: !!attrs.auto_exclude,
              onChange: (v) => props.setAttributes({ auto_exclude: v })
            }),

            el(TextareaControl, {
              label: "Exclude Tags (IDs)",
              value: attrs.exclude_raw || "",
              onChange: (v) => props.setAttributes({ exclude_raw: v }),
              onBlur: () => props.setAttributes({ exclude: toNumbers(attrs.exclude_raw) })
            })
          )
        ),

        el(
          "div",
          { className: `${pluginName}-preview`, style: { marginTop: "8px" } },
          el(serverSideRender, {
            key: "preview",
            block: typeName,
            attributes: attrs
          })
        )
      );
    },

    save: () => null
  });

})(window.wp);
