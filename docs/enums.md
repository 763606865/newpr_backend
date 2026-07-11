# Project Enums Reference

本文件列出 app/Enums 目录下的枚举类型及其候选值与中文标签，便于前后端对照、生成 API 文档、以及在表单下拉和后端校验时快速查阅。

## RcResumeSourceType (int)
- Upload (1): 上传
- Parse (2): 解析
- Manual (3): 手工创建
- Import (4): 导入

## RcApplicationStatus (int)
- Pending (0): 待处理
- Screening (1): 筛选中
- Interviewing (2): 面试中
- Offering (3): Offer中
- Hired (4): 录用
- Rejected (5): 淘汰
- Withdrawn (6): 撤回

## SApiClientStatus (int)
- Disabled (0): 停用
- Enabled (1): 启用

## CompanyOperationAction (string)
- Created: 创建企业
- Updated: 更新企业信息
- Deleted: 删除企业
- StatusChanged: 变更企业状态
- AuditApproved: 审批通过
- AuditRejected: 审批拒绝
- PlanBound: 绑定套餐
- PlanRefreshed: 刷新套餐
- PlanBatchRebound: 批量重绑套餐

## RcPortfolioType (int)
- Link (1): 链接
- Image (2): 图片
- Video (3): 视频
- Document (4): 文档
- Other (5): 其他

## CompanyPlanStatus (int)
- Disabled (0): 失效
- Enabled (1): 生效中
- Pause (2): 暂停维护

## RcOfferStatus (int)
- Draft (0): 草稿
- Sent (1): 已发送
- Accepted (2): 已接受
- Rejected (3): 已拒绝
- Expired (4): 已过期
- Revoked (5): 已撤销

## CmsAnnouncementPublisherType (int)
- System (0): 系统
- StateOwnedEnterprise (1): 国有企业
- CentralEnterprise (2): 中央企业
- PublicInstitution (3): 事业单位
- Government (4): 政府机关
- Bank (5): 银行
- School (6): 学校
- PrivateEnterprise (7): 民营企业
- ForeignEnterprise (8): 外资企业
- JointVenture (9): 合资企业
- Hospital (10): 医院
- ResearchInstitute (11): 科研院所
- IndustryAssociation (12): 行业协会
- SocialOrganization (13): 社会组织
- ListedCompany (14): 上市公司
- NonProfitOrganization (15): 非营利组织
- Military (16): 军队
- Other (99): 其他

## RcMaritalStatus (int)
- Unknown (0): 未知
- Single (1): 未婚
- Married (2): 已婚
- Divorced (3): 离异
  - Widowed (4): 丧偶

## RcCurrentIdentity (int)
- Other (0): 其他
- WorkingPerson (1): 职场人
- Student (2): 学生
- Unemployed (3): 待业

## RcSchoolActivityMode (int)
- Online (1): 线上
- Offline (2): 线下

## RcLanguageProficiency (int)
- Beginner (1): 入门
- Conversational (2): 日常交流
- Business (3): 商务谈判
- Fluent (4): 精通

## MajorEducationType (string)
- Undergraduate: 本科
- VocationalSecondary: 中职
- HigherVocational: 高职专科
- VocationalUndergraduate: 职教本科

## CmsStatus (int)
- Disabled (0): 禁用
- Enabled (1): 启用

## SchoolProfileStatus (int)
- Disabled (0): 禁用
- Normal (1): 正常
- Reviewing (2): 审核中

## RcTalentPoolMemberSourceType (int)
- Manual (1): 主动加入
- JobInflow (2): 职位沉淀
- Import (3): 导入
- Recommendation (4): 推荐

## RcSkillProficiency (int)
- Aware (1): 了解
- Familiar (2): 熟悉
- Proficient (3): 熟练
- Expert (4): 精通

## EmployeeStatus (int)
- Active (1): 在职
- Dismissed (0): 离职

## RcInterviewMode (int)
- Online (1): 线上
- Offline (2): 线下
- Phone (3): 电话

## AttendanceClockLogPunchType (int)
- ClockIn (1): 上班卡
- ClockOut (2): 下班卡
- Supplement (3): 补卡
- SystemAdjust (4): 系统修正

## RcNotificationType (int)
- InterviewInvitation (1): 面试邀请
- OfferSent (2): Offer通知
- ApplicationStatusChanged (3): 投递状态变更
- InterviewInvitationAccepted (4): 面试邀请已接受
- InterviewInvitationRejected (5): 面试邀请已拒绝
- OfferAcceptedByCandidate (6): Offer已接受
- OfferRejectedByCandidate (7): Offer已拒绝
- SchoolActivityCompanyInvited (8): 校招活动邀约
- SchoolActivityCompanyApproved (9): 校招活动审批通过

## AttendanceClockState (string)
- ClockIn: 上班卡
- ClockOut: 下班卡
- Finished: 已完成
- Unavailable: 不可打卡

## RcApplicationSourceType (int)
- Direct (1): 主动投递
- Referral (2): 内推
- Headhunter (3): 猎头
- Campus (4): 校招
- Government (5): 政府渠道
- Import (6): 导入

## LeaveTypeDeductionType (int)
- Full (1): 带薪
- Half (2): 半薪
- None (3): 无薪

## CmsArticleContentType (int)
- Html (1): HTML
- Markdown (2): Markdown

## CmsHomeRecommendationModuleType (int)
- UrgentJob (1): 紧急招聘
- HotJob (2): 热招职位
- FamousCompany (3): 名企招聘
- CampusHotCompany (4): 校招-热门公司
- CampusHotJob (5): 热门校招

## RcCertificateType (int)
- Certificate (1): 证书
- Honor (2): 荣誉奖项

## RcIdentityStatus (int)
- Enabled (1): 启用
- Disabled (0): 停用

## AttendanceClockLogClockResult (int)
- Valid (1): 有效
- Invalid (2): 无效
- OutOfWindowRejected (3): 超窗拒绝
- Duplicate (4): 重复

## RcSchoolBoothStatus (int)
- Disabled (0): 禁用
- Enabled (1): 启用

## CompanyFundingStage (int)
- Unfunded (1): 未融资
- Angel (2): 天使轮
- SeriesA (3): A轮
- SeriesB (4): B轮
- SeriesC (5): C轮
- SeriesDPlus (6): D轮及以上
- Listed (7): 已上市
- NoNeed (8): 不需要融资

## RcSalaryUnit (int)
- Month (1): 月
- Day (2): 日
- Hour (3): 时

## RcSchoolActivityOrganizerType (string)
- School: 学校
- Company: 企业
- Area: 区域

## RcInterviewResult (int)
- Pending (0): 待评估
- Passed (1): 通过
- Failed (2): 不通过
- ToBeDetermined (3): 待定

## CompanyProfileStatus (int)
- Draft (0): 草稿
- Complete (1): 已完善
- Auditing (2): 审核中

## CompanyBenefitTag (string)
- SocialInsurance: 五险一金
- HousingFund: 住房公积金
- WeekendOff: 双休
- FlexibleWork: 弹性工作
- AnnualBonus: 年终奖
- PaidLeave: 带薪年假
- MealAllowance: 餐补
- TransportAllowance: 交通补贴
- StockOption: 股票期权
- Training: 培训机会

## RcIdentityType (int)
- JobSeeker (1): 求职者
- Recruiter (2): 招聘方
- CampusManager (3): 校招负责人
- GovernmentManager (4): 政府机构负责人
- Headhunter (5): 猎头

## AttendanceClockLogClockMethod (int)
- App (1): APP
- Web (2): WEB
- Admin (3): 管理员代打
- Import (4): 导入

## DepartmentType (int)
- Function (1): 职能
- Business (2): 业务
- Leader (3): 管理层

## CmsLinkType (int)
- Internal (1): 站内
- External (2): 站外
- None (3): 无跳转

## MajorStatus (int)
- Disabled (0): 禁用
- Enabled (1): 启用

## CompanyContactType (int)
- LegalPerson (1): 法定代表人
- Shareholder (2): 股东
- Contact (3): 联系人
- ActualController (4): 实际控制人
- Other (5): 其他

## RcJobStatus (int)
- Draft (0): 草稿
- Published (1): 已发布
- Paused (2): 暂停
- Closed (3): 关闭
- Expired (4): 过期

## CmsAnnouncementType (int)
- System (1): 系统公告
- Official (2): 官方公告
- ExamRecruitment (3): 招考公告
- JobRecruitment (4): 招聘公告
- LocalPolicy (5): 地方政策公告
- University (6): 高校公告

## RcEducationLevel (int)
- HighSchool (1): 高中/中专
- Associate (2): 专科
- Bachelor (3): 本科
- Master (4): 硕士
- Doctor (5): 博士
- Other (6): 其他

## MajorLevel (int)
- Category (1): 大类
- Discipline (2): 专业类
- Major (3): 专业

## CompanyStatus (int)
- Disabled (0): 禁用
- Auditing (2): 审批中
- Enabled (1): 启用

## RcEmploymentType (int)
- FullTime (1): 全职
- PartTime (2): 兼职
- Internship (3): 实习

## CompanyNatureType (int)
- Private (1): 民营企业
- StateOwned (2): 国有企业
- Foreign (3): 外资企业
- JointVenture (4): 合资企业
- PublicInstitution (5): 事业单位
- NonProfit (6): 非营利组织
- Other (7): 其他

## RcInterviewStatus (int)
- Pending (0): 待安排
- Scheduled (1): 已安排
- Finished (2): 已完成
- Cancelled (3): 已取消
- AwaitingCandidate (4): 待候选人确认

## RcResumeJobStatus (int)
- OpenToOpportunity (1): 在职考虑机会
- NotConsidering (2): 在职不考虑
- ActivelyLooking (3): 离职找工作
- FreshGraduate (4): 应届生

## CmsOpenTarget (int)
- Self (1): 当前页
- Blank (2): 新窗口

## RcTalentPoolStatus (int)
- Enabled (1): 启用
- Disabled (0): 停用

## AttendanceRuleWorkType (int)
- Fixed (1): 固定
- Group (2): 分段
- Variable (3): 弹性

## RcSchoolActivityApplyStatus (int)
- Pending (0): 待审核
- Approved (1): 通过
- Rejected (2): 驳回

## SystemMenuType (int)
- Menu (1): 菜单
- Button (2): 按钮/权限点

## CompanyBUserStatus (int)
- Disabled (0): 禁用
- Enabled (1): 启用

## RcSchoolActivityBusinessStatus (int)
- Draft (0): 草稿
- Upcoming (1): 未开始
- Registering (2): 报名中
- Ongoing (3): 进行中
- Ended (4): 已结束

## ClockMode (int)
- Normal (1)
- ForceOverwrite (2)

## UserStatus (string)
- Active: 激活
- Resolved: 修复中
- Closed: 已关闭

## AttendanceScheduleStatus (int)
- Pending (0): 待计算
- Normal (1): 正常
- Late (2): 迟到
- Early (3): 早退
- MissingCard (4): 缺卡
- Absence (5): 旷工

## RcApplicationFlowActionType (int)
- Transfer (1): 流转
- Note (2): 备注
- Withdraw (3): 撤回
- Reject (4): 淘汰
- Hire (5): 录用

## CmsTagCategory (string)
- Rc: 招聘类型 (rc)
- Exam: 考试 (exam)
- ExamRecruitment: 招考 (exam_recruitment)
- SchoolExam: 学校考试 (school_exam)
- CertificateExam: 证书考试 (certificate_exam)
- Announcement: 公告 (announcement)
- Article: 文章 (article)
- Job: 职位 (job)

## SystemPlanStatus (int)
- Disabled (0): 禁用
- Enabled (1): 启用

## RcAnnouncementApplyDeadlineType (int)
- Fixed (1): 指定截止日期
- UntilFilled (2): 招满即止

## RcJobStageStatus (int)
- Enabled (1): 启用
- Disabled (0): 停用

## RcJobEmploymentType (int)
- FullTime (1): 社招全职
- PartTime (2): 兼职招聘
- Internship (3): 实习生招聘
- Campus (4): 应届校园招聘
- Outsource (5): 派遣外包

## AreaLevel (int)
- Country (0): 国
- Province (1): 省
- City (2): 市
- District (3): 区县

## CmsMenuAudienceType (int)
- Guest (0): 游客
- JobSeeker (1): 求职者
- Recruiter (2): 招聘方
- CampusManager (3): 校招负责人
- GovernmentManager (4): 政府机构负责人
- Headhunter (5): 猎头

## UserGender (int)
- Unknown (0): 未知
- Male (1): 男
- Female (2): 女

## CmsPublishStatus (int)
- Draft (1): 草稿
- Published (2): 已发布
- Offline (3): 下线

## CmsAdType (int)
- Image (1): 图片
- Text (2): 文本
- Code (3): 代码

## RcResumeStatus (int)
- Normal (1): 正常
- Disabled (0): 停用

## CompanyLicenseType (int)
- BusinessLicense (1): 营业执照
- FoodSafetyPermit (2): 食品经营许可证
- Qualification (3): 资质证书
- Other (4): 其他

## RcApplicationStatus (int)
- see above


---

Generated from app/Enums. If you want a different layout (individual files per enum, JSON output, or include case descriptions), reply with preference.

# Enums 逐项说明

本文件为每个 enum 提供一句中文说明，方便在接口文档和前端下拉中理解用途。详细候选值请参见 docs/enums.md。

使用说明：此文件只给出简短用途描述。如需为某个枚举补充示例、字段来源或后端字段名，请回复具体 enum 名称。

---

## RcResumeSourceType
说明：表示简历来源（上传、解析、手工创建、导入），用于 resume.source 或相关 API 字段。

## RcApplicationStatus
说明：表示投递/申请的处理状态（待处理、筛选、面试、Offer、录用等），用于投递流程跟踪。

## SApiClientStatus
说明：表示第三方 API 客户端开关状态（启用/停用），用于管理面板和校验。

## CompanyOperationAction
说明：公司操作类型（字符串事件名），用于操作日志或事件分发。

## RcPortfolioType
说明：简历作品类型（链接、图片、视频、文档、其他），用于候选人展示作品。

## CompanyPlanStatus
说明：企业套餐计划状态（生效/失效/暂停），用于计费与权限判断。

## RcOfferStatus
说明：Offer 状态（草稿、已发送、接受、拒绝等），用于 offer 流程管理。

## CmsAnnouncementPublisherType
说明：公告发布方类型（系统、企业、学校等），用于公告筛选与展示。

## RcMaritalStatus
说明：婚姻状况枚举，用于候选人信息字段。

## RcCurrentIdentity
说明：当前身份类型（职场人、学生、待业等），用于用户身份标识与权限。

## RcSchoolActivityMode
说明：校招活动形式（线上/线下），用于活动类型与前端展示。

## RcLanguageProficiency
说明：语言熟练度等级（入门、日常、商务、精通），用于简历技能描述。

## MajorEducationType
说明：专业教育类型标签（本科、中职等），用于教育项的 level/类型说明。

## CmsStatus
说明：通用 CMS 状态（启用/禁用），用于后台管理项的可用性控制。

## SchoolProfileStatus
说明：学校资料状态（正常/审核中/禁用），用于学校端显示与权限。

## RcTalentPoolMemberSourceType
说明：人才池成员来源类型（主动加入、职位沉淀、导入、推荐），用于来源统计。

## RcSkillProficiency
说明：技能熟练度（了解、熟悉、熟练、精通），用于候选人技能标签与筛选。

## EmployeeStatus
说明：员工在职状态（在职/离职），用于员工管理模块。

## RcInterviewMode
说明：面试方式（线上、线下、电话），用于面试安排与通知内容。

## AttendanceClockLogPunchType
说明：打卡类型（上班/下班/补卡/系统修正），用于考勤记录分类。

## RcNotificationType
说明：系统通知类型（面试邀请、Offer、投递状态等），用于推送与模板选择。

## AttendanceClockState
说明：考勤卡状态字符串，用于标注打卡类型的文本值。

## RcApplicationSourceType
说明：投递来源（主动、内推、猎头、校招、政府、导入），用于投递统计与渠道分析。

## LeaveTypeDeductionType
说明：请假扣薪类型（带薪/半薪/无薪），用于假期扣薪计算逻辑。

## CmsArticleContentType
说明：文章内容类型（HTML/Markdown），用于前端渲染选择与编辑器配置。

## CmsHomeRecommendationModuleType
说明：首页推荐模块类型（紧急/热招/名企等），用于内容模块分类与组件渲染。

## RcCertificateType
说明：证书类型（证书/荣誉），用于简历证书项分类。

## RcIdentityStatus
说明：身份记录是否启用，用于身份管理与校验。

## AttendanceClockLogClockResult
说明：打卡结果（有效/无效/超窗拒绝/重复），用于考勤规则判定。

## RcSchoolBoothStatus
说明：校招展位状态（启用/禁用），用于展位管理。

## CompanyFundingStage
说明：企业融资阶段枚举（天使、A/B/C、已上市等），用于公司信息展示与筛选。

## RcSalaryUnit
说明：薪资单位（月/日/时），用于职位薪资字段的单位说明。

## RcSchoolActivityOrganizerType
说明：校招活动主办方类型（学校/企业/区域），用于活动关联与权限。

## RcInterviewResult
说明：面试结果（待评估/通过/不通过/待定）。

## CompanyProfileStatus
说明：公司资料完善状态（草稿/已完善/审核中），用于是否可发布职位等判断。

## CompanyBenefitTag
说明：公司福利标签（五险一金、年终奖等），用于职位/公司展示。

## RcIdentityType
说明：用户身份类型（求职者/招聘方/校招负责人等），用于权限与页面展示。

## AttendanceClockLogClockMethod
说明：打卡方法（APP/Web/管理员导入等），用于记录来源。

## DepartmentType
说明：部门类型（职能/业务/管理），用于组织架构与权限分组。

## CmsLinkType
说明：链接类型（站内/站外/无跳转），用于前端链接行为设定。

## MajorStatus
说明：专业/学科状态（启用/禁用），用于后台管理。

## CompanyContactType
说明：公司联系人类型（法定代表人/股东/联系人等），用于公司信息表单。

## RcJobStatus
说明：职位状态（草稿/已发布/暂停/关闭/过期），用于职位列表与筛选。

## CmsAnnouncementType
说明：公告类型分类（系统公告、官方、招考、招聘等），用于公告管理页分类筛选。

## RcEducationLevel
说明：教育层次（高中/专科/本科/硕士/博士/其他），用于简历教育项。

## MajorLevel
说明：专业层级（大类/专业类/具体专业），用于专业数据结构。

## CompanyStatus
说明：企业整体状态（禁用/审批中/启用），用于是否可见/可操作。

## RcEmploymentType
说明：招聘类型（全职/兼职/实习），用于职位基本信息。

## CompanyNatureType
说明：企业性质（民营/国有/外资等），用于公司档案与筛选。

## RcInterviewStatus
说明：面试流程状态（待安排/已安排/已完成/已取消/待候选人确认）。

## RcResumeJobStatus
说明：简历的求职状态（在职考虑/在职不考虑/离职找工作/应届生），用于匹配与筛选。

## CmsOpenTarget
说明：链接打开方式（当前页/新窗口），用于前端渲染。

## RcTalentPoolStatus
说明：人才池启用状态（启用/停用），用于人才池管理。

## AttendanceRuleWorkType
说明：排班类型（固定/分段/弹性），用于考勤规则配置。

## RcSchoolActivityApplyStatus
说明：报名状态（待审核/通过/驳回），用于活动报名管理。

## SystemMenuType
说明：菜单项类型（菜单/按钮权限点），用于权限与前端菜单渲染。

## CompanyBUserStatus
说明：企业 B 端用户状态（启用/禁用），用于用户管理。

## RcSchoolActivityBusinessStatus
说明：活动运行状态（草稿/未开始/报名中/进行中/已结束），用于活动展示。

## ClockMode
说明：打卡模式标识（普通/强制覆盖），用于考勤操作控制。

## UserStatus
说明：通用用户状态字符串（激活、修复中、已关闭），用于问题/工单等场景。

## AttendanceScheduleStatus
说明：排班计算后状态（待计算/正常/迟到/早退/缺卡/旷工）。

## RcApplicationFlowActionType
说明：投递流转操作类型（流转、备注、撤回、淘汰、录用），用于操作记录。

## CmsTagCategory
说明：CMS 标签分类（招聘/考试/公告/文章/职位等），用于文章/内容的分类标记。

## SystemPlanStatus
说明：系统计划/策略状态（启用/禁用），用于功能开关。

## RcAnnouncementApplyDeadlineType
说明：公告报名截止类型（指定截止/招满即止），用于活动/报名类公告。

## RcJobStageStatus
说明：职位阶段状态（启用/停用），用于职位阶段管理。

## RcJobEmploymentType
说明：职位招聘渠道/类型（社招全职、兼职、实习、校园、派遣），用于职位分类。

## AreaLevel
说明：行政区划级别（国家/省/市/区县），用于地区数据层级。

## CmsMenuAudienceType
说明：菜单受众类型（游客/求职者/招聘方等），用于菜单可见性控制。

## UserGender
说明：用户性别（未知/男/女），用于用户信息字段。

## CmsPublishStatus
说明：内容发布状态（草稿/已发布/下线），用于文章/公告发布流程。

## CmsAdType
说明：广告位类型（图片/文本/代码），用于前端广告渲染。

## RcResumeStatus
说明：简历状态（正常/停用），用于简历可见性控制。

## CompanyLicenseType
说明：企业证照类型（营业执照/食品许可证/资质/其他），用于公司资料上传。

## RcApplicationStatus
说明：同 RcApplicationStatus（重复），用于投递流程的状态字段。

---

如需我把这些说明直接插入 docs/enums.md 的每个 enum 段落下（替换或添加），回复“插入到主文档”，我会在原文件中逐段插入.
