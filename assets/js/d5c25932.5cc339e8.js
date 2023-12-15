"use strict";(self.webpackChunkwebsite=self.webpackChunkwebsite||[]).push([[782],{8281:(e,n,t)=>{t.r(n),t.d(n,{assets:()=>a,contentTitle:()=>d,default:()=>h,frontMatter:()=>l,metadata:()=>c,toc:()=>r});var s=t(5893),i=t(1151);const l={id:"entities",title:"Entities",slug:"/entities"},d=void 0,c={id:"entities",title:"Entities",description:"You can create entities by extending Access/Entity and implementing some",source:"@site/../docs/entities.md",sourceDirName:".",slug:"/entities",permalink:"/docs/entities",draft:!1,unlisted:!1,editUrl:"https://github.com/justim/access/edit/master/website/../docs/entities.md",tags:[],version:"current",frontMatter:{id:"entities",title:"Entities",slug:"/entities"},sidebar:"docs",previous:{title:"Database",permalink:"/docs/database"},next:{title:"Cascade",permalink:"/docs/cascade"}},a={},r=[{value:"Table name",id:"table-name",level:2},{value:"Fields",id:"fields",level:2},{value:"Special fields",id:"special-fields",level:3},{value:"ID",id:"id",level:4},{value:"Timestamps",id:"timestamps",level:4},{value:"Soft delete",id:"soft-delete",level:4},{value:"Access fields",id:"access-fields",level:3},{value:"Full example",id:"full-example",level:2}];function o(e){const n={code:"code",em:"em",h2:"h2",h3:"h3",h4:"h4",li:"li",p:"p",pre:"pre",table:"table",tbody:"tbody",td:"td",th:"th",thead:"thead",tr:"tr",ul:"ul",...(0,i.a)(),...e.components};return(0,s.jsxs)(s.Fragment,{children:[(0,s.jsxs)(n.p,{children:["You can create entities by extending ",(0,s.jsx)(n.code,{children:"Access/Entity"})," and implementing some\nmethods to tell Access what your entity is about. The methods you must implement\nare ",(0,s.jsx)(n.code,{children:"tableName"})," and ",(0,s.jsx)(n.code,{children:"fields"}),", first is used to query the right database table\nand the second is used access the right fields of that table."]}),"\n",(0,s.jsx)(n.h2,{id:"table-name",children:"Table name"}),"\n",(0,s.jsxs)(n.p,{children:["A very straight forward method to get the name of the table that is used for all\noperations on this entity, ",(0,s.jsx)(n.code,{children:"tableName"})," just return a string with the name of the\ntable."]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"use Access\\Entity;\n\nclass User extends Entity\n{\n    /**\n     * Get the table name for this entity\n     */\n    public static function tableName(): string\n    {\n        return 'users';\n    }\n}\n"})}),"\n",(0,s.jsx)(n.h2,{id:"fields",children:"Fields"}),"\n",(0,s.jsxs)(n.p,{children:["The result of ",(0,s.jsx)(n.code,{children:"fields"})," is an array with the field name for a key and a\ndefinition as its value."]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"use Access\\Entity;\n\nclass User extends Entity\n{\n    /**\n     * Get all fields needed for this entity to work\n     */\n    public static function fields(): array\n    {\n        return [\n            'username' => [\n                // some definition\n            ],\n        ];\n    }\n}\n"})}),"\n",(0,s.jsx)(n.p,{children:"The definition supports the following options:"}),"\n",(0,s.jsxs)(n.ul,{children:["\n",(0,s.jsxs)(n.li,{children:["\n",(0,s.jsxs)(n.p,{children:[(0,s.jsx)(n.code,{children:"type"}),": The type of the field, to be used in the conversion from and to the database. Allowed values:"]}),"\n",(0,s.jsxs)(n.table,{children:[(0,s.jsx)(n.thead,{children:(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.th,{children:"Type"}),(0,s.jsx)(n.th,{children:"From database"}),(0,s.jsx)(n.th,{children:"To database"})]})}),(0,s.jsxs)(n.tbody,{children:[(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.td,{children:(0,s.jsx)(n.em,{children:"default"})}),(0,s.jsxs)(n.td,{children:[(0,s.jsx)(n.code,{children:"string"})," ",(0,s.jsx)(n.em,{children:"(no conversion is done)"})]}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.em,{children:"(no conversion is done)"})})]}),(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"self::FIELD_TYPE_INT"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"int"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.em,{children:"(no conversion is done)"})})]}),(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"self::FIELD_TYPE_BOOL"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"bool"})}),(0,s.jsxs)(n.td,{children:[(0,s.jsx)(n.code,{children:"1"}),"/",(0,s.jsx)(n.code,{children:"0"})]})]}),(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"self::FIELD_TYPE_DATE"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"\\DateTimeImmutable"})}),(0,s.jsxs)(n.td,{children:[(0,s.jsx)(n.code,{children:"Y-m-d"})," formatted string"]})]}),(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"self::FIELD_TYPE_DATETIME"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"\\DateTimeImmutable"})}),(0,s.jsxs)(n.td,{children:[(0,s.jsx)(n.code,{children:"Y-m-d H:i:s"})," formatted string"]})]}),(0,s.jsxs)(n.tr,{children:[(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"self::FIELD_TYPE_JSON"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"json_decode(<value>, true)"})}),(0,s.jsx)(n.td,{children:(0,s.jsx)(n.code,{children:"json_encode(<value>)"})})]})]})]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"'is_admin' => [\n    'type' => self::FIELD_TYPE_BOOL,\n],\n"})}),"\n"]}),"\n",(0,s.jsxs)(n.li,{children:["\n",(0,s.jsxs)(n.p,{children:[(0,s.jsx)(n.code,{children:"default"}),": The default value of the field when inserting a new entity, will\ngo through the conversion defined by the ",(0,s.jsx)(n.code,{children:"type"}),". When a callback is used, it\nwill be called on demand with the entity as the only parameter, useful for\ndates, generating random values."]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"'profile_image' => [\n    'default' => null,\n],\n"})}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"'secret' => [\n    'default' => fn() => mt_rand(0, 1000),\n],\n"})}),"\n"]}),"\n",(0,s.jsxs)(n.li,{children:["\n",(0,s.jsxs)(n.p,{children:[(0,s.jsx)(n.code,{children:"virtual"}),": A boolean indicating this value is not a real value, but is\nincluded in the entity as the result of a join/subquery. Will not be saved."]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"'total_projects' => [\n    'virtual' => true,\n],\n"})}),"\n"]}),"\n",(0,s.jsxs)(n.li,{children:["\n",(0,s.jsxs)(n.p,{children:[(0,s.jsx)(n.code,{children:"excludeInCopy"}),": When creating a copy with ",(0,s.jsx)(n.code,{children:"Entity::copy"})," this field will be\nexcluded"]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"'published_at' => [\n    'excludeInCopy' => true,\n],\n"})}),"\n"]}),"\n"]}),"\n",(0,s.jsx)(n.h3,{id:"special-fields",children:"Special fields"}),"\n",(0,s.jsxs)(n.p,{children:["There are a couple of special fields that you don't need to specify in the\nresult of ",(0,s.jsx)(n.code,{children:"fields"}),", namely:"]}),"\n",(0,s.jsx)(n.h4,{id:"id",children:"ID"}),"\n",(0,s.jsxs)(n.p,{children:["The ",(0,s.jsx)(n.code,{children:"id"})," field will be managed automatically by Access and can not be changes\nonce set, the ",(0,s.jsx)(n.code,{children:"Entity"})," class provides a ",(0,s.jsx)(n.code,{children:"getId"})," to access it. This method will\nthrow is the ID is not available this the entity, if you want to know if the ID\nis available there is ",(0,s.jsx)(n.code,{children:"hasId"}),"."]}),"\n",(0,s.jsx)(n.h4,{id:"timestamps",children:"Timestamps"}),"\n",(0,s.jsxs)(n.p,{children:["It is possible to enable the timestamp fields ",(0,s.jsx)(n.code,{children:"created_at"})," and ",(0,s.jsx)(n.code,{children:"updated_at"})," by\nimplementing the ",(0,s.jsx)(n.code,{children:"timestamps"})," method and returning ",(0,s.jsx)(n.code,{children:"true"}),". These fields will no\nbe available inside the entity. An easy way to get this working is by using the\n",(0,s.jsx)(n.code,{children:"TimestampableTrait"}),", this will implement the ",(0,s.jsx)(n.code,{children:"timestamps"})," method and provide a\ncouple of getters for the fields."]}),"\n",(0,s.jsxs)(n.p,{children:["Once enabled the ",(0,s.jsx)(n.code,{children:"Entity"})," class will update the fields when needed, on insert\nand on on update. No need to do this yourself."]}),"\n",(0,s.jsx)(n.h4,{id:"soft-delete",children:"Soft delete"}),"\n",(0,s.jsxs)(n.p,{children:["Access supports soft deleting entities out of the box with the ",(0,s.jsx)(n.code,{children:"deleted_at"}),"\nfield, it can be enabled for an entity by implementing ",(0,s.jsx)(n.code,{children:"isSoftDeletable"})," and\nreturning ",(0,s.jsx)(n.code,{children:"true"}),". Once the fields is filled, Access will no longer return the\nentity from any of the query it runs. An easy way to get this working is by\nusing the ",(0,s.jsx)(n.code,{children:"SoftDeletableTrait"}),", this will implement the ",(0,s.jsx)(n.code,{children:"isSoftDeletable"})," method\nand provide a couple of getters/setters for the fields."]}),"\n",(0,s.jsxs)(n.p,{children:["The ",(0,s.jsx)(n.code,{children:"Database"})," instance has a helper method to soft delete with a single method\ncall, instead of setting the ",(0,s.jsx)(n.code,{children:"deleted_at"})," field and saving it:\n",(0,s.jsx)(n.code,{children:"Database::softDelete()"}),"."]}),"\n",(0,s.jsx)(n.h3,{id:"access-fields",children:"Access fields"}),"\n",(0,s.jsxs)(n.p,{children:["Now that we have all fields defined, we need a way to access them from the\noutside. The values itself are privately stored inside the ",(0,s.jsx)(n.code,{children:"Entity"})," class and\ncan only be accessed by the protected getter (",(0,s.jsx)(n.code,{children:"get"}),") and setter (",(0,s.jsx)(n.code,{children:"set"}),")."]}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",children:"use Access\\Entity;\n\nclass User extends Entity\n{\n    /**\n     * Get the value of the `username` field\n     */\n    public function getUsername(): string\n    {\n        return $this->get('username');\n    }\n\n    /**\n     * Set the value of the `username` field\n     */\n    public function setUsername(string $username): void\n    {\n        $this->set('username', $username);\n    }\n}\n"})}),"\n",(0,s.jsx)(n.h2,{id:"full-example",children:"Full example"}),"\n",(0,s.jsx)(n.p,{children:"A complete example"}),"\n",(0,s.jsx)(n.pre,{children:(0,s.jsx)(n.code,{className:"language-php",metastring:'title="User.php"',children:"<?php\n\nuse Access\\Entity;\n\nclass User extends Entity\n{\n    /**\n     * Get the table name for this entity\n     */\n    public static function tableName(): string\n    {\n        return 'users';\n    }\n\n    /**\n     * Get all fields needed for this entity to work\n     */\n    public static function fields(): array\n    {\n        return [\n            'username' => [\n                // by default a field is of the type string/varchar/text\n            ],\n            'is_admin' => [\n                // Access will convert this field from/to bool\n                'type' => self::FIELD_TYPE_BOOL,\n\n                // the default value for when inserting a new entity\n                'default' => false,\n            ],\n            'metadata' => [\n                // Access will convert this field from/to json\n                'type' => self::FIELD_TYPE_JSON,\n            ],\n        ];\n    }\n\n    /**\n     * Get the value of the `username` field\n     */\n    public function getUsername(): string\n    {\n        return $this->get('username');\n    }\n\n    /**\n     * Set the value of the `username` field\n     */\n    public function setUsername(string $username): void\n    {\n        $this->set('username', $username);\n    }\n}\n"})})]})}function h(e={}){const{wrapper:n}={...(0,i.a)(),...e.components};return n?(0,s.jsx)(n,{...e,children:(0,s.jsx)(o,{...e})}):o(e)}},1151:(e,n,t)=>{t.d(n,{Z:()=>c,a:()=>d});var s=t(7294);const i={},l=s.createContext(i);function d(e){const n=s.useContext(l);return s.useMemo((function(){return"function"==typeof e?e(n):{...n,...e}}),[n,e])}function c(e){let n;return n=e.disableParentContext?"function"==typeof e.components?e.components(i):e.components||i:d(e.components),s.createElement(l.Provider,{value:n},e.children)}}}]);