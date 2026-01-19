(({
  serverSideRender,
  blocks: { registerBlockType },
  element: { createElement: el, useState },
  blockEditor: { InspectorControls, useBlockProps },
  components: {
    PanelBody,
    ToggleControl,
    ComboboxControl,
    Button,
    RangeControl,
  },
  data: { useSelect },
  coreData: { store: coreStore }
}) => {

  const pluginName = 'dreamy-tags';
  const typeName = `lewismoten/${pluginName}`;
  const deleteIcon = "\u00D7"; // multiplication sign for ascii-only source file

  const asNumber = (value) => {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
  };

  // Small “chip” UI helpers (inline, wrap, compact)
  const chipWrapStyle = {
    display: "flex",
    flexWrap: "wrap",
    gap: "6px",
    marginTop: "8px"
  };

  const chipStyle = {
    display: "inline-flex",
    alignItems: "center",
    gap: "6px",
    padding: "2px 8px",
    borderRadius: "999px",
    border: "1px solid rgba(0,0,0,0.15)",
    background: "rgba(0,0,0,0.04)",
    fontSize: "12px",
    lineHeight: "20px"
  };

  const chipLabelStyle = {
    whiteSpace: "nowrap",
    maxWidth: "220px",
    overflow: "hidden",
    textOverflow: "ellipsis"
  };

  const chipXStyle = {
    minWidth: "auto",
    padding: "0 6px",
    height: "20px",
    lineHeight: "18px"
  };

  const previewImage = window.DreamyTagsBlock?.previewImage;

  registerBlockType(typeName, {
    edit: (props) => {
      const {isPreview, ...attrs} = props.attributes || {};
      if ( isPreview && previewImage ) {
        return el("div", useBlockProps(), 
          el("img", {
            src: previewImage,
            alt: "Dreamy Tags block preview",
            style: { width: "100%", height: "auto", display: "block" }
          })
        );
      }

      const blockProps = useBlockProps({ className: `${pluginName}-editor` });

      const [catSearch, setCatSearch] = useState("");
      const [tagSearch, setTagSearch] = useState("");
      const [excludeSearch, setExcludeSearch] = useState("");

      // -------------------------
      // Categories
      // -------------------------
      const allCats = useSelect(
        (select) =>
          select(coreStore).getEntityRecords("taxonomy", "category", {
            search: catSearch || undefined,
            per_page: 50,
            hide_empty: false,
            orderby: "name",
            order: "asc"
          }),
        [catSearch]
      );

      const catOptions = (allCats || []).map((c) => ({
        label: c.name,
        value: c.id
      }));

      const selectedCatIds = Array.isArray(attrs.cat)
        ? attrs.cat.map(asNumber).filter((n) => n !== null)
        : [];

      const selectedCats = useSelect(
        (select) => {
          if (!selectedCatIds.length) return [];
          return select(coreStore).getEntityRecords("taxonomy", "category", {
            include: selectedCatIds,
            per_page: selectedCatIds.length,
            hide_empty: false
          });
        },
        [selectedCatIds.join(",")]
      );

      const catNameById = {};
      (selectedCats || []).forEach((c) => {
        catNameById[c.id] = c.name;
      });

      const addCat = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        if (!selectedCatIds.includes(idNum)) {
          props.setAttributes({ cat: [...selectedCatIds, idNum] });
        }
      };

      const removeCat = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        props.setAttributes({ cat: selectedCatIds.filter((c) => c !== idNum) });
      };

      // -------------------------
      // Filter Tags (include)
      // -------------------------
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

      // -------------------------
      // Exclude Tags
      // -------------------------
      const excludeAllTags = useSelect(
        (select) =>
          select(coreStore).getEntityRecords("taxonomy", "post_tag", {
            search: excludeSearch || undefined,
            per_page: 50,
            hide_empty: false,
            orderby: "name",
            order: "asc"
          }),
        [excludeSearch]
      );

      const excludeOptions = (excludeAllTags || []).map((t) => ({
        label: t.name,
        value: t.id
      }));

      const excludeTagIds = Array.isArray(attrs.exclude)
        ? attrs.exclude.map(asNumber).filter((n) => n !== null)
        : [];

      // Fetch selected include tags by ID so names show on initial load
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

      const tagNameById = {};
      (selectedTags || []).forEach((t) => {
        tagNameById[t.id] = t.name;
      });

      // Fetch selected exclude tags by ID so names show on initial load
      const excludedTags = useSelect(
        (select) => {
          if (!excludeTagIds.length) return [];
          return select(coreStore).getEntityRecords("taxonomy", "post_tag", {
            include: excludeTagIds,
            per_page: excludeTagIds.length,
            hide_empty: false
          });
        },
        [excludeTagIds.join(",")]
      );

      const excludeNameById = {};
      (excludedTags || []).forEach((t) => {
        excludeNameById[t.id] = t.name;
      });

      const addTag = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        // Optional: prevent overlap with exclude list
        if (excludeTagIds.includes(idNum)) return;
        if (!selectedTagIds.includes(idNum)) {
          props.setAttributes({ tags: [...selectedTagIds, idNum] });
        }
      };

      const removeTag = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        props.setAttributes({ tags: selectedTagIds.filter((t) => t !== idNum) });
      };

      const addExcludeTag = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        // Optional: prevent overlap with include list
        if (selectedTagIds.includes(idNum)) return;
        if (!excludeTagIds.includes(idNum)) {
          props.setAttributes({ exclude: [...excludeTagIds, idNum] });
        }
      };

      const removeExcludeTag = (id) => {
        const idNum = asNumber(id);
        if (!idNum) return;
        props.setAttributes({ exclude: excludeTagIds.filter((t) => t !== idNum) });
      };

      // -------------------------
      // UI
      // -------------------------
      return el(
        "div",
        blockProps,

        el(
          InspectorControls,
          { key: "inspector" },
          el(
            PanelBody,
            { title: "Dreamy Tags Settings", initialOpen: true },

            // Categories picker
            el(ComboboxControl, {
              label: "Filter Categories",
              help: "Type to search categories, then click to add",
              options: catOptions,
              value: null,
              onFilterValueChange: (input) => setCatSearch(input || ""),
              onChange: (catId) => addCat(catId)
            }),

            // Category chips (inline/wrapping)
            el(
              "div",
              { style: chipWrapStyle },
              selectedCatIds.length
                ? selectedCatIds.map((id) => {
                  const label = catNameById[id] || `Category #${id}`;
                  return el(
                    "span",
                    { key: id, style: chipStyle },
                    el("span", { style: chipLabelStyle, title: label }, label),
                    el(
                      Button,
                      {
                        isSmall: true,
                        isDestructive: true,
                        type: "button",
                        style: chipXStyle,
                        onClick: (e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          removeCat(id);
                        }
                      },
                      deleteIcon
                    )
                  );
                })
                : el("span", { style: { opacity: 0.7 } }, "No categories selected yet.")
            ),

            el(ToggleControl, {
              label: "Include posts in child categories",
              checked: !!attrs.children,
              onChange: (v) => props.setAttributes({ children: v })
            }),

            // Filter Tags picker
            el(ComboboxControl, {
              label: "Filter Tags",
              help: "Type to search tags, then click to add",
              options: tagOptions,
              value: null,
              onFilterValueChange: (input) => setTagSearch(input || ""),
              onChange: (tagId) => addTag(tagId)
            }),

            // Filter tag chips
            el(
              "div",
              { style: chipWrapStyle },
              selectedTagIds.length
                ? selectedTagIds.map((id) => {
                  const label = tagNameById[id] || `Tag #${id}`;
                  return el(
                    "span",
                    { key: id, style: chipStyle },
                    el("span", { style: chipLabelStyle, title: label }, label),
                    el(
                      Button,
                      {
                        isSmall: true,
                        isDestructive: true,
                        type: "button",
                        style: chipXStyle,
                        onClick: (e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          removeTag(id);
                        }
                      },
                      deleteIcon
                    )
                  );
                })
                : el("span", { style: { opacity: 0.7 } }, "No filter tags selected yet.")
            ),

            el(ToggleControl, {
              label: "Auto-exclude filtered tags",
              checked: !!attrs.auto_exclude,
              onChange: (v) => props.setAttributes({ auto_exclude: v })
            }),

            // Exclude Tags picker
            el(ComboboxControl, {
              label: "Exclude Tags",
              help: "Type to search tags, then click to exclude",
              options: excludeOptions,
              value: null,
              onFilterValueChange: (input) => setExcludeSearch(input || ""),
              onChange: (tagId) => addExcludeTag(tagId)
            }),

            // Exclude tag chips
            el(
              "div",
              { style: chipWrapStyle },
              excludeTagIds.length
                ? excludeTagIds.map((id) => {
                  const label = excludeNameById[id] || `Tag #${id}`;
                  return el(
                    "span",
                    { key: id, style: chipStyle },
                    el("span", { style: chipLabelStyle, title: label }, label),
                    el(
                      Button,
                      {
                        isSmall: true,
                        isDestructive: true,
                        type: "button",
                        style: chipXStyle,
                        onClick: (e) => {
                          e.preventDefault();
                          e.stopPropagation();
                          removeExcludeTag(id);
                        }
                      },
                      deleteIcon
                    )
                  );
                })
                : el("span", { style: { opacity: 0.7 } }, "No excluded tags yet.")
            ),
            // Minimum posts per tag
            el(RangeControl, {
              label: "Minimum posts per tag",
              help: "Only show tags used on at least this many matching posts.",
              min: 1,
              max: 50,
              value: Number.isFinite(attrs.min_count) ? attrs.min_count : 1,
              onChange: (v) => props.setAttributes({ min_count: v || 1 })
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
