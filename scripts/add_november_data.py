#!/usr/bin/env python3
import json
from pathlib import Path

DATA_DIR = Path('backup')
NUM_RECORDS = 20
YEAR = 2025
MONTH = 11
PASSWORD_HASH = "$2a$12$N1GocFct6mkIJSWWU4M5sOQVYnI6lortWck28EfrLJ58euANVuHDq"

# Files that store docs in different shapes
FILE_STRUCT = {
    'appointments.json': 'docs',
    'consultations.json': 'docs',
    'doctors.json': 'docs',
    'patients.json': 'docs',
    'staffs.json': 'docs',
    'users.json': 'docs',
    'invoices.json': 'rows',
    'medical_records.json': 'rows',
    'medical_tests.json': 'rows',
    'medications.json': 'rows',
    'treatments.json': 'rows',
    'roles.json': 'rows'
}

LAST_NAMES = [
    'Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Đặng', 'Đỗ', 'Vũ', 'Bùi', 'Đinh',
    'Phan', 'Huỳnh', 'Võ', 'Dương', 'Cao', 'Tạ', 'Mai', 'Ngô', 'Châu', 'Tô'
]
MIDDLE_NAMES = ['Văn', 'Thị', 'Hữu', 'Quang', 'Ngọc', 'Gia', 'Minh', 'Thanh', 'Thùy', 'Phương']
MALE_FIRST = ['Minh', 'Huy', 'Khôi', 'Thắng', 'Dũng', 'Khoa', 'Trung', 'Nhân', 'Phúc', 'Long']
FEMALE_FIRST = ['Lan', 'Anh', 'Hà', 'Hương', 'Trang', 'Ly', 'Vy', 'Nhung', 'Quỳnh', 'Thu']
BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']
CHRONIC_CONDITIONS = [
    ('I10', 'Tăng huyết áp nguyên phát'),
    ('E11', 'Đái tháo đường type 2'),
    ('J45', 'Hen phế quản'),
    ('K21', 'Trào ngược dạ dày thực quản'),
    ('E78', 'Rối loạn mỡ máu'),
    ('M54', 'Đau thắt lưng mạn tính'),
    ('G43', 'Đau nửa đầu'),
    ('I25', 'Bệnh mạch vành'),
    ('N18', 'Suy thận mạn'),
    ('M15', 'Thoái hóa khớp đa ổ')
]
ALLERGIES = ['Penicillin', 'Seafood', 'Peanuts', 'Dust', 'Latex', 'NSAIDs']
INSURANCE_PROVIDERS = ['BHYT', 'PTI', 'BaoViet', 'Prudential']
SPECIALTIES = ['Tim mạch', 'Nội tiết', 'Tiêu hóa', 'Thần kinh', 'Cơ xương khớp', 'Hô hấp', 'Tai mũi họng', 'Da liễu', 'Nhi khoa', 'Ung bướu']
STAFF_TYPES = ['nurse', 'receptionist', 'lab_technician', 'care_coordinator', 'pharmacist']
DEPARTMENTS = ['Outpatient', 'Inpatient', 'Diagnostics', 'Customer Care', 'Pharmacy']
APPOINTMENT_TYPES = ['consultation', 'follow_up', 'telehealth']
APPOINTMENT_PRIORITIES = ['normal', 'high', 'urgent']
APPOINTMENT_REASONS = [
    'Khám định kỳ kiểm tra sức khỏe',
    'Theo dõi điều trị tăng huyết áp',
    'Điều chỉnh toa thuốc tiểu đường',
    'Tư vấn cải thiện giấc ngủ',
    'Đau dạ dày kéo dài',
    'Tái khám sau phẫu thuật',
    'Đánh giá dị ứng thời tiết',
    'Kiểm tra chức năng gan',
    'Đánh giá viêm khớp gối',
    'Khó thở về đêm'
]
TEST_TYPES = [
    ('blood_work', 'Tổng phân tích máu'),
    ('chemistry', 'Sinh hóa máu'),
    ('imaging', 'X-quang ngực thẳng'),
    ('cardiology', 'Điện tâm đồ 12 chuyển đạo'),
    ('endocrine', 'Định lượng HbA1c'),
    ('renal', 'Chức năng thận'),
    ('lipid', 'Hồ sơ mỡ máu'),
    ('inflammatory', 'CRP định lượng'),
    ('coagulation', 'Đông máu toàn bộ'),
    ('thyroid', 'TSH & FT4')
]
CONSULTATION_MESSAGES = [
    'Em bị đau đầu mỗi tối, cần đặt lịch khám thế nào ạ?',
    'Thuốc uống sau ăn mà em lại uống trước ăn, có sao không anh/chị?',
    'Em muốn đổi lịch hẹn sang buổi sáng được không ạ?',
    'Bé nhà em ho nhiều về đêm, cần mua thuốc gì hỗ trợ?',
    'Em vừa nhận kết quả xét nghiệm, nhờ anh/chị giải thích giúp',
    'Có gói khám tổng quát gia đình trong tháng 11 không ạ?',
    'Em cần cấp lại toa thuốc tháng 10 thì làm sao ạ?',
    'Bố em bị tăng huyết áp, cần đặt lịch đo huyết áp hàng tuần',
    'Sau khi chụp MRI em thấy hơi chóng mặt, có bình thường không?',
    'Bệnh viện có hỗ trợ tư vấn dinh dưỡng online không ạ?'
]
MEDICATION_TEMPLATES = [
    ('CardioSafe', '10mg', 'tablet', 'Beta blocker'),
    ('GlucoBalance', '500mg', 'tablet', 'Biguanide'),
    ('LipoGuard', '20mg', 'tablet', 'Statin'),
    ('NeuroCalm', '75mg', 'capsule', 'Neuropathic agent'),
    ('GastroShield', '40mg', 'capsule', 'Proton pump inhibitor'),
    ('PulmoEase', '5mg', 'tablet', 'Leukotriene receptor antagonist'),
    ('JointFlex', '1500mg', 'tablet', 'Chondroprotective'),
    ('RenalCare', '25mg', 'tablet', 'ACE inhibitor'),
    ('ImmunoPlus', '1g', 'vial', 'Immunoglobulin'),
    ('Dermasoft', '1%', 'ointment', 'Topical steroid')
]
ROLE_PERMISSIONS = [
    ['patient:read', 'appointment:read', 'consultation:read'],
    ['patient:read', 'appointment:write', 'consultation:write'],
    ['patient:read', 'record:read', 'record:write'],
    ['treatment:read', 'treatment:write', 'medication:read'],
    ['invoice:read', 'invoice:write', 'report:read']
]


def compose_name(index):
    gender = 'male' if index % 2 else 'female'
    last = LAST_NAMES[index % len(LAST_NAMES)]
    middle = MIDDLE_NAMES[index % len(MIDDLE_NAMES)]
    first = MALE_FIRST[index % len(MALE_FIRST)] if gender == 'male' else FEMALE_FIRST[index % len(FEMALE_FIRST)]
    return f"{last} {middle} {first}", gender


def make_phone(index, prefix='090'):
    tail = ''.join(str((index * mult) % 10) for mult in [7, 9, 11, 13, 15, 17, 19])
    return f"{prefix}{tail[:7]}"


def make_iso(day, hour, minute):
    day = max(1, min(day, 30))
    return f"{YEAR}-{MONTH:02d}-{day:02d}T{hour:02d}:{minute:02d}:00Z"


def load_file(filename):
    path = DATA_DIR / filename
    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    storage_type = FILE_STRUCT.get(filename, 'docs')
    if storage_type == 'docs':
        docs = data.get('docs', [])
    elif storage_type == 'rows':
        docs = data.get('rows', [])
    else:
        docs = data if isinstance(data, list) else data.get('docs', [])
    return data, docs, storage_type


def save_file(filename, container, items, storage_type):
    path = DATA_DIR / filename
    if storage_type == 'docs':
        container['docs'] = items
    elif storage_type == 'rows':
        container['rows'] = items
        container['total_rows'] = len(items)
    else:
        container = items
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(container, f, ensure_ascii=False, indent=2)


def existing_ids(items, storage_type):
    ids = set()
    if storage_type == 'docs':
        for doc in items:
            if isinstance(doc, dict) and doc.get('_id'):
                ids.add(doc['_id'])
    else:
        for row in items:
            if not isinstance(row, dict):
                continue
            if isinstance(row.get('doc'), dict) and row['doc'].get('_id'):
                ids.add(row['doc']['_id'])
            elif row.get('_id'):
                ids.add(row['_id'])
    return ids


def append_entry(items, storage_type, doc):
    if storage_type == 'docs':
        items.append(doc)
    else:
        items.append({
            'id': doc.get('_id'),
            'key': doc.get('_id'),
            'value': {'rev': doc.get('_rev', '1-new')},
            'doc': doc
        })


def build_medications():
    meds = []
    lookup = []
    for i in range(1, NUM_RECORDS + 1):
        template = MEDICATION_TEMPLATES[(i - 1) % len(MEDICATION_TEMPLATES)]
        name, strength, dosage_form, therapeutic = template
        med_id = f"med_2025_11_{i:03d}"
        doc = {
            '_id': med_id,
            'type': 'medication',
            'medication_info': {
                'name': name,
                'generic_name': name,
                'strength': strength,
                'dosage_form': dosage_form,
                'manufacturer': 'Medicare Pharma',
                'barcode': f"89360{(i + 100):05d}",
                'inventory': {
                    'unit_cost': 45000 + (i * 1500)
                }
            },
            'clinical_info': {
                'therapeutic_class': therapeutic,
                'indications': [CHRONIC_CONDITIONS[(i - 1) % len(CHRONIC_CONDITIONS)][1]],
                'contraindications': ['Hypersensitivity'],
                'side_effects': ['Chóng mặt nhẹ', 'Buồn nôn'],
                'drug_interactions': ['Alcohol']
            },
            'inventory': {
                'current_stock': 180 + (i * 12),
                'unit_cost': 45000 + (i * 1500),
                'expiry_date': '2027-12-31',
                'supplier': 'VietPharma Distribution'
            },
            'status': 'active',
            'created_at': make_iso((i % 27) + 1, 9, 0),
            'updated_at': make_iso((i % 27) + 1, 11, 0)
        }
        meds.append(doc)
        lookup.append({'id': med_id, 'name': name, 'strength': strength})
    return meds, lookup


def build_bundle(index, med_ref):
    day = (index % 27) + 1
    follow_day = (day % 27) + 1
    name, gender = compose_name(index)
    patient_id = f"patient_2025_11_{index:03d}"
    doctor_name, _ = compose_name(index + 30)
    doctor_id = f"doctor_2025_11_{index:03d}"
    staff_name, _ = compose_name(index + 60)
    staff_id = f"staff_2025_11_{index:03d}"
    appointment_id = f"appointment_2025_11_{index:03d}"
    record_id = f"record_2025_11_{index:03d}"
    invoice_id = f"invoice_2025_11_{index:03d}"
    treatment_id = f"treatment_2025_11_{index:03d}"
    test_id = f"test_2025_11_{index:03d}"
    consultation_id = f"consultation_2025_11_{index:03d}"
    user_id = f"user_patient_2025_11_{index:03d}"
    phone = make_phone(index)
    emergency_phone = make_phone(index + 100, prefix='091')
    birth_year = 1955 + (index % 15)
    birth_month = ((index * 3) % 12) + 1
    birth_day = ((index * 5) % 25) + 1
    birth_date = f"{birth_year}-{birth_month:02d}-{birth_day:02d}"
    blood_type = BLOOD_TYPES[index % len(BLOOD_TYPES)]
    condition_code, condition_desc = CHRONIC_CONDITIONS[index % len(CHRONIC_CONDITIONS)]
    allergy = ALLERGIES[index % len(ALLERGIES)]
    insurance = INSURANCE_PROVIDERS[index % len(INSURANCE_PROVIDERS)]
    specialty = SPECIALTIES[index % len(SPECIALTIES)]
    staff_type = STAFF_TYPES[index % len(STAFF_TYPES)]
    department = DEPARTMENTS[index % len(DEPARTMENTS)]
    appointment_type = APPOINTMENT_TYPES[index % len(APPOINTMENT_TYPES)]
    priority = APPOINTMENT_PRIORITIES[index % len(APPOINTMENT_PRIORITIES)]
    reason = APPOINTMENT_REASONS[index % len(APPOINTMENT_REASONS)]
    visit_type = appointment_type
    test_type, test_name = TEST_TYPES[index % len(TEST_TYPES)]
    message_text = CONSULTATION_MESSAGES[index % len(CONSULTATION_MESSAGES)]

    patient_doc = {
        '_id': patient_id,
        'type': 'patient',
        'personal_info': {
            'full_name': name,
            'birth_date': birth_date,
            'gender': gender,
            'id_number': f"079{index:03d}{follow_day:02d}11",
            'phone': phone,
            'email': f"patient{index:03d}@demo-clinic.com",
            'emergency_contact': {
                'name': f"{LAST_NAMES[(index + 3) % len(LAST_NAMES)]} {MIDDLE_NAMES[(index + 2) % len(MIDDLE_NAMES)]} {MALE_FIRST[(index + 4) % len(MALE_FIRST)]}",
                'relationship': 'family',
                'phone': emergency_phone
            }
        },
        'address': {
            'street': f"{(index * 5) % 200 + 20} Nguyễn Huệ",
            'ward': 'Bến Nghé',
            'district': 'Quận 1',
            'city': 'TP. Hồ Chí Minh',
            'postal_code': '700000'
        },
        'medical_info': {
            'blood_type': blood_type,
            'allergies': [allergy],
            'chronic_conditions': [condition_desc],
            'insurance': {
                'provider': insurance,
                'policy_number': f"{insurance}-{YEAR}{index:03d}",
                'valid_until': '2026-12-31'
            }
        },
        'status': 'active',
        'created_at': make_iso(day, 8, 15),
        'updated_at': make_iso(day, 10, 45)
    }

    doctor_doc = {
        '_id': doctor_id,
        'type': 'doctor',
        'personal_info': {
            'full_name': f'BS. {doctor_name}',
            'gender': 'male' if (index + 1) % 2 else 'female',
            'phone': make_phone(index + 200),
            'email': f"doctor{index:03d}@demo-clinic.com"
        },
        'professional_info': {
            'specialty': specialty,
            'license_number': f"MD-{YEAR}{index:03d}",
            'experience_years': 8 + (index % 10),
            'qualifications': ['MD', 'Specialist Level 1']
        },
        'schedule': {
            'working_days': ['mon', 'tue', 'thu', 'fri'],
            'working_hours': '08:00-17:00'
        },
        'contact_info': {
            'room': f"P{(index % 12) + 201}",
            'hotline': '19001725'
        },
        'status': 'active',
        'created_at': make_iso(day, 7, 45),
        'updated_at': make_iso(day, 9, 15)
    }

    staff_doc = {
        '_id': staff_id,
        'type': 'staff',
        'full_name': staff_name,
        'staff_type': staff_type,
        'gender': 'female' if (index + 1) % 2 else 'male',
        'phone': make_phone(index + 300, prefix='089'),
        'email': f"staff{index:03d}@demo-clinic.com",
        'department': department,
        'shift': {
            'days': ['mon', 'tue', 'wed', 'thu', 'fri'],
            'start': '07:30',
            'end': '16:30'
        },
        'status': 'active',
        'created_at': make_iso(day, 7, 0),
        'updated_at': make_iso(day, 7, 30)
    }

    appointment_doc = {
        '_id': appointment_id,
        'type': 'appointment',
        'patient_id': patient_id,
        'doctor_id': doctor_id,
        'appointment_info': {
            'scheduled_date': make_iso(day, 9 + (index % 5), 0),
            'duration': 30 + (index % 3) * 15,
            'type': appointment_type,
            'priority': priority
        },
        'reason': reason,
        'status': 'completed' if day < 25 else 'scheduled',
        'notes': 'Lưu ý mang theo kết quả xét nghiệm gần nhất',
        'reminders': [
            {
                'type': 'sms',
                'sent_at': make_iso(day - 1 if day > 1 else day, 18, 0),
                'status': 'sent'
            }
        ],
        'created_at': make_iso(day - 2 if day > 2 else day, 12, 0),
        'updated_at': make_iso(day, 11, 0),
        'created_by': staff_id
    }

    visit_date = make_iso(day, 9 + (index % 4), 30)
    medical_record_doc = {
        '_id': record_id,
        'type': 'medical_record',
        'patient_id': patient_id,
        'doctor_id': doctor_id,
        'visit_info': {
            'visit_date': visit_date,
            'visit_type': visit_type,
            'chief_complaint': reason,
            'appointment_id': appointment_id
        },
        'examination': {
            'vital_signs': {
                'temperature': 36.5 + (index % 3) * 0.1,
                'blood_pressure': {
                    'systolic': 118 + (index % 5) * 4,
                    'diastolic': 76 + (index % 4) * 3
                },
                'heart_rate': 70 + (index % 6),
                'respiratory_rate': 18,
                'weight': 60 + (index % 10),
                'height': 160 + (index % 8)
            },
            'physical_exam': {
                'general': 'Tỉnh táo, giao tiếp tốt',
                'cardiovascular': 'Nhịp đều',
                'respiratory': 'Phổi thông khí tốt'
            }
        },
        'diagnosis': {
            'primary': {
                'code': condition_code,
                'description': condition_desc,
                'severity': 'moderate'
            },
            'secondary': [],
            'differential': []
        },
        'treatment_plan': {
            'medications': [
                {
                    'name': med_ref['name'],
                    'dosage': med_ref['strength'],
                    'frequency': '1 lần/ngày',
                    'duration': '30 ngày',
                    'instructions': 'Uống sau ăn'
                }
            ],
            'procedures': [],
            'lifestyle_advice': [
                'Uống đủ nước',
                'Tập thể dục 30 phút/ngày'
            ],
            'follow_up': {
                'date': make_iso(follow_day, 9, 0).split('T')[0],
                'notes': 'Tái khám theo hẹn'
            }
        },
        'status': 'completed',
        'created_at': visit_date,
        'updated_at': make_iso(day, 13, 0)
    }

    base_amount = 450000 + index * 12000
    tax_rate = 0.08
    tax_amount = round(base_amount * tax_rate)
    total_amount = base_amount + tax_amount
    invoice_doc = {
        '_id': invoice_id,
        'type': 'invoice',
        'patient_id': patient_id,
        'medical_record_id': record_id,
        'invoice_info': {
            'invoice_number': f"INV-2025-11-{index:03d}",
            'invoice_date': make_iso(day, 14, 30),
            'due_date': make_iso(day + 7 if day <= 23 else 30, 17, 0)
        },
        'services': [
            {
                'service_type': 'consultation',
                'description': 'Khám chuyên khoa',
                'quantity': 1,
                'unit_price': base_amount - 150000,
                'total_price': base_amount - 150000
            },
            {
                'service_type': 'lab',
                'description': test_name,
                'quantity': 1,
                'unit_price': 150000,
                'total_price': 150000
            }
        ],
        'payment_info': {
            'subtotal': base_amount,
            'tax_rate': tax_rate,
            'tax_amount': tax_amount,
            'total_amount': total_amount,
            'patient_payment': total_amount,
            'insurance_coverage': 0.0,
            'insurance_amount': 0
        },
        'payment_status': 'paid',
        'payment_method': 'bank_transfer',
        'paid_date': make_iso(day, 15, 15),
        'created_at': make_iso(day, 14, 0),
        'updated_at': make_iso(day, 15, 20)
    }

    treatment_doc = {
        '_id': treatment_id,
        'type': 'treatment',
        'patient_id': patient_id,
        'doctor_id': doctor_id,
        'medical_record_id': record_id,
        'treatment_info': {
            'treatment_name': f'Phác đồ kiểm soát {condition_desc.lower()}',
            'start_date': visit_date,
            'end_date': make_iso(day + 20 if day <= 10 else 30, 9, 0),
            'duration_days': 30,
            'treatment_type': 'medication'
        },
        'medications': [
            {
                'medication_id': med_ref['id'],
                'name': med_ref['name'],
                'dosage': med_ref['strength'],
                'frequency': '1 lần/ngày',
                'route': 'oral',
                'instructions': 'Uống sau ăn',
                'quantity_prescribed': 30
            }
        ],
        'monitoring': {
            'parameters': ['blood_pressure', 'heart_rate'],
            'frequency': 'weekly',
            'next_check': make_iso(day + 7 if day <= 23 else 30, 8, 0)
        },
        'status': 'active' if index % 3 else 'completed',
        'created_at': visit_date,
        'updated_at': make_iso(day, 16, 0)
    }

    medical_test_doc = {
        '_id': test_id,
        'type': 'medical_test',
        'patient_id': patient_id,
        'doctor_id': doctor_id,
        'medical_record_id': record_id,
        'test_info': {
            'test_type': test_type,
            'test_name': test_name,
            'ordered_date': visit_date,
            'sample_collected_date': make_iso(day, 11, 0),
            'result_date': make_iso(follow_day, 16, 0),
            'unit_price': 150000 + (index % 5) * 20000
        },
        'results': {
            'primary': {
                'value': round(1.0 + (index % 5) * 0.2, 1),
                'unit': 'index',
                'reference_range': '0.5-1.5',
                'status': 'normal'
            },
            'secondary': {
                'value': 80 + index,
                'unit': 'mg/dL',
                'reference_range': '70-110',
                'status': 'normal'
            }
        },
        'interpretation': 'Kết quả trong giới hạn cho phép',
        'status': 'completed',
        'lab_technician': f'tech_{index:03d}',
        'created_at': visit_date,
        'updated_at': make_iso(follow_day, 16, 30)
    }

    consultation_doc = {
        '_id': consultation_id,
        'type': 'consultation',
        'patient_id': patient_id,
        'patient_info': {
            'name': name,
            'phone': phone,
            'avatar': None
        },
        'staff_id': staff_id,
        'staff_info': {
            'name': staff_name,
            'staff_type': staff_type,
            'avatar': None
        },
        'status': ['active', 'closed', 'waiting'][index % 3],
        'last_message': message_text,
        'last_message_at': make_iso(day, 19, 0),
        'unread_count_patient': 0,
        'unread_count_staff': 1 if index % 3 == 2 else 0,
        'created_at': make_iso(day, 18, 0),
        'updated_at': make_iso(day, 19, 5)
    }

    message_patient = {
        '_id': f'message_2025_11_{index:03d}_a',
        'type': 'message',
        'consultation_id': consultation_id,
        'sender_id': patient_id,
        'sender_type': 'patient',
        'sender_name': name,
        'message': message_text,
        'is_read': False,
        'created_at': make_iso(day, 18, 0)
    }

    message_staff = {
        '_id': f'message_2025_11_{index:03d}_b',
        'type': 'message',
        'consultation_id': consultation_id,
        'sender_id': staff_id,
        'sender_type': 'staff',
        'sender_name': staff_name,
        'message': 'Bộ phận CSKH đã nhận được thông tin và sẽ hỗ trợ ngay.',
        'is_read': True,
        'created_at': make_iso(day, 18, 30)
    }

    user_doc = {
        '_id': user_id,
        'type': 'user',
        'username': f'patient.2025_11_{index:03d}',
        'email': f'patient{index:03d}@demo-clinic.com',
        'password_hash': PASSWORD_HASH,
        'role_names': ['patient'],
        'account_type': 'patient',
        'linked_patient_id': patient_id,
        'status': 'active',
        'created_at': make_iso(day, 8, 0),
        'updated_at': make_iso(day, 8, 30)
    }

    bundle = {
        'patient': patient_doc,
        'doctor': doctor_doc,
        'staff': staff_doc,
        'appointment': appointment_doc,
        'medical_record': medical_record_doc,
        'invoice': invoice_doc,
        'treatment': treatment_doc,
        'medical_test': medical_test_doc,
        'consultation': consultation_doc,
        'messages': [message_patient, message_staff],
        'user': user_doc
    }
    return bundle


def build_role_doc(index):
    role_id = f'role_support_2025_11_{index:03d}'
    return {
        '_id': role_id,
        'type': 'role',
        'name': f'care_team_{index:03d}',
        'display_name': f'Nhóm chăm sóc {index:02d}',
        'permissions': ROLE_PERMISSIONS[index % len(ROLE_PERMISSIONS)],
        'description': 'Vai trò hỗ trợ vận hành chiến dịch tháng 11/2025',
        'status': 'active',
        'created_at': make_iso((index % 27) + 1, 8, 0),
        'updated_at': make_iso((index % 27) + 1, 9, 0)
    }


def main():
    meds, med_lookup = build_medications()
    bundles = [build_bundle(i, med_lookup[(i - 1) % len(med_lookup)]) for i in range(1, NUM_RECORDS + 1)]

    file_docs = {
        'patients.json': [b['patient'] for b in bundles],
        'doctors.json': [b['doctor'] for b in bundles],
        'staffs.json': [b['staff'] for b in bundles],
        'appointments.json': [b['appointment'] for b in bundles],
        'medical_records.json': [b['medical_record'] for b in bundles],
        'medical_tests.json': [b['medical_test'] for b in bundles],
        'treatments.json': [b['treatment'] for b in bundles],
        'invoices.json': [b['invoice'] for b in bundles],
        'consultations.json': [b['consultation'] for b in bundles] + [msg for b in bundles for msg in b['messages']],
        'users.json': [b['user'] for b in bundles],
        'medications.json': meds,
        'roles.json': [build_role_doc(i) for i in range(1, NUM_RECORDS + 1)]
    }

    summary = {}
    for filename, new_docs in file_docs.items():
        container, items, storage_type = load_file(filename)
        ids = existing_ids(items, storage_type)
        added = 0
        for doc in new_docs:
            doc_id = doc.get('_id')
            if not doc_id or doc_id in ids:
                continue
            append_entry(items, storage_type, doc)
            ids.add(doc_id)
            added += 1
        save_file(filename, container, items, storage_type)
        summary[filename] = added

    print('Added documents:')
    for filename, count in summary.items():
        print(f"- {filename}: {count}")


if __name__ == '__main__':
    main()
